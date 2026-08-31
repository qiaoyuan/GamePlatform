<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\GameAccount;
use app\common\model\GameAccountApiLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Eldorado 平台 API 客户端
 *
 * 认证：POST /api/authentication/seller/token
 *   body JSON: {"clientId":"...","clientSecret":"..."}
 *   返回:  {"accessToken":"...","expiresIn":899,"tokenType":"Bearer"}
 *
 * 改价（当前在用）：updateOfferPrice() —— 整单提交
 *   POST /api/v1/currency-management/me/offers
 *   Content-Type: application/json-patch+json
 *   除价格外的字段取自已同步的 offer_data
 *
 * 改价（旧方式，保留不再默认调用）：updatePrice() —— 单接口改价，A/B 双接口互为降级
 *   PUT /api/predefinedOffersUser/me/{offerId}/changePrice        [接口 A]
 *   PUT /api/v1/currency-management/me/offers/{offerId}/change-price [接口 B]
 *   body JSON: {"amount": 0.04, "currency": "USD"}
 *
 * 同步线上数据：GET /api/v1/currency-management/me/offers/{offerId}
 *   与改价共用同一个账号级 token
 */
class EldoradoClient
{
    private GameAccount $account;
    private Client $http;

    /** 两个改价接口路径，按顺序尝试（哪个未被风控就用哪个） */
    private const PRICE_URLS = [
        'A' => '/api/predefinedOffersUser/me/%s/changePrice',
        'B' => '/api/v1/currency-management/me/offers/%s/change-price',
    ];

    /** 单个接口 429 后的冷却时长（秒）*/
    private const RATE_LIMIT_TTL = 420;

    /** 冷却 key 版本号，升版本即废弃历史 key（旧 key 自然过期，不再被读取）*/
    private const RATE_LIMIT_KEY_VERSION = 'v2';

    public function __construct(GameAccount $account)
    {
        $this->account = $account;
        $this->http = new Client([
            'base_uri' => config('eldorado.base_uri', 'https://www.eldorado.gg'),
            'timeout'  => config('eldorado.timeout', 15),
        ]);
    }

    /**
     * 获取完整的 Authorization 头值（tokenType + ' ' + accessToken），优先读缓存
     *
     * @throws \RuntimeException 获取失败时抛出
     */
    public function getAccessToken(): string
    {
        $cacheKey = 'eldorado_access_token_' . $this->account->id;
        $cache = cache()->store('redis');
        $token = $cache->get($cacheKey);
        if ($token) {
            return $token;
        }
        $token = $this->refreshAccessToken();
        $cache->set($cacheKey, $token, config('eldorado.token_cache_ttl', 800));
        return $token;
    }

    /**
     * 强制刷新并返回完整的 Authorization 头值（tokenType + ' ' + accessToken），并记录调用日志
     *
     * @throws \RuntimeException 刷新失败时抛出
     */
    public function refreshAccessToken(): string
    {
        $url   = '/api/authentication/seller/token';
        $requestData = [
            'clientId'     => $this->account->client_id,
            'clientSecret' => $this->account->client_secret,
        ];
        $start = microtime(true);

        try {
            $res  = $this->http->post($url, [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'json'    => $requestData,
            ]);
            $body = (string) $res->getBody();
            $json = json_decode($body, true) ?? [];
            $duration = (int) ((microtime(true) - $start) * 1000);

            $accessToken = $json['accessToken'] ?? '';
            $tokenType   = $json['tokenType']   ?? 'Bearer';
            $success     = $accessToken !== '';

            $this->log(
                GameAccountApiLog::TYPE_REFRESH_TOKEN,
                $url,
                $this->maskSensitive($requestData),
                $json,
                $success,
                $success ? '' : $this->extractError($json, 'token 获取失败'),
                $duration
            );

            if (!$success) {
                throw new \RuntimeException(
                    'Eldorado token 获取失败: ' . $this->extractError($json)
                );
            }
            // 返回完整的 Authorization 头值，tokenType 由平台响应决定
            return $tokenType . ' ' . $accessToken;
        } catch (GuzzleException $e) {
            $duration = (int) ((microtime(true) - $start) * 1000);
            $this->log(GameAccountApiLog::TYPE_REFRESH_TOKEN, $url, $this->maskSensitive($requestData), null, false, $e->getMessage(), $duration);
            throw new \RuntimeException('Eldorado token 请求异常: ' . $e->getMessage());
        }
    }

    /**
     * 改价
     *
     * 逻辑：
     * 1. 检查两个改价接口是否都在 429 冷却中，都冷却中则直接抛异常跳过（不发请求）。
     * 2. 按 A → B 顺序选取第一个未冷却的接口发起请求。
     * 3. 任意接口返回 429 → 写该产品对应接口 10 分钟冷却标记；token 按账号共用，同步清掉。
     *    冷却 key 以 offerId 为维度，不同产品互不影响。
     *
     * @param string $offerId       Eldorado 平台 offer ID（即 product_id）
     * @param float  $price         新价格（USD）
     * @param int    $gameProductId 本地产品 ID（仅用于日志关联）
     * @throws \RuntimeException 改价失败或风控中时抛出
     */
    public function updatePrice(string $offerId, float $price, int $gameProductId = 0): array
    {
        $redisCache = cache()->store('redis');
        $accountId  = $this->account->id;

        // 各接口的 429 冷却缓存 key（按产品 offerId 隔离，不同产品冷却互不影响；token 仍按账号共用）
        $rateLimitKeys = [
            'A' => 'eld_rl_' . self::RATE_LIMIT_KEY_VERSION . '_A_' . $offerId,
            'B' => 'eld_rl_' . self::RATE_LIMIT_KEY_VERSION . '_B_' . $offerId,
        ];

        // 检查哪些接口还未冷却
        $availableKeys = [];
        foreach ($rateLimitKeys as $tag => $rlKey) {
            if (!$redisCache->get($rlKey)) {
                $availableKeys[$tag] = $rlKey;
            }
        }

        // 两个接口都在冷却中 → 直接跳过，不发请求
        if (empty($availableKeys)) {
            throw new \RuntimeException('ELD风控限制中，请稍后再试（两个改价接口均在10分钟冷却中）');
        }

        // 取第一个可用接口（优先 A）
        $tag         = array_key_first($availableKeys);
        $url         = sprintf(self::PRICE_URLS[$tag], $offerId);
        $rateLimitKey = $rateLimitKeys[$tag];

        $requestData = [
            'amount'   => $price,
            'currency' => 'USD',
        ];
        $start = microtime(true);

        try {
            $res  = $this->http->put($url, [
                'headers' => [
                    'Authorization' => $this->getAccessToken(),
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => $requestData,
            ]);
            $body = (string) $res->getBody();
            $json = json_decode($body, true) ?? [];
            $duration = (int) ((microtime(true) - $start) * 1000);

            $statusCode = $res->getStatusCode();
            $success    = $statusCode >= 200 && $statusCode < 300;

            $this->log(
                GameAccountApiLog::TYPE_UPDATE_PRICE,
                $url,
                $requestData,
                $json,
                $success,
                $success ? '' : $this->extractError($json, '改价失败'),
                $duration,
                $gameProductId
            );

            if (!$success) {
                throw new \RuntimeException('Eldorado 改价失败: ' . $this->extractError($json));
            }
            return $json;
        } catch (GuzzleException $e) {
            $duration = (int) ((microtime(true) - $start) * 1000);
            $respJson = null;
            $errMsg   = $e->getMessage();
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $respBody = (string) $e->getResponse()->getBody();
                $respJson = json_decode($respBody, true);
                $errMsg   = $this->extractError($respJson, $e->getMessage());

                if ($e->getResponse()->getStatusCode() === 429) {
                    // 标记当前产品的当前接口 10 分钟冷却；token 按账号共用，同样清掉让下次重新取
                    $redisCache->set($rateLimitKey, 1, self::RATE_LIMIT_TTL);
                    $redisCache->delete('eldorado_access_token_' . $this->account->id);
                }
            }
            $this->log(GameAccountApiLog::TYPE_UPDATE_PRICE, $url, $requestData, $respJson, false, $errMsg, $duration, $gameProductId);
            throw new \RuntimeException('Eldorado 改价失败: ' . $errMsg);
        }
    }

    /**
     * 改价（当前在用的方式）：整单提交 offer
     *
     * POST /api/v1/currency-management/me/offers
     *   Content-Type: application/json-patch+json
     *
     * 除价格以外的参数（quantity / minQuantity / currency / deliveryMethod /
     * gameId / category / tradeEnvironmentId）全部来自「同步线上数据」写入的 offer_data，
     * 只有 pricePerUnit.amount 用调用方传入的新价格覆盖。
     *
     * 注意：本接口会连带提交 quantity（库存）。offer_data 是同步那一刻的快照，
     * 若线上库存之后有变动，改价会把库存写回快照值，因此改价前建议先同步。
     *
     * @param string $offerId       Eldorado 平台 offer ID（即 product_id，仅用于风控冷却 key 与日志）
     * @param array  $offerData     同步下来的 offer_data
     * @param float  $price         新价格（pricePerUnit.amount）
     * @param int    $gameProductId 本地产品 ID（仅用于日志关联）
     * @throws \RuntimeException 参数不全 / 风控中 / 改价失败时抛出
     */
    public function updateOfferPrice(string $offerId, array $offerData, float $price, int $gameProductId = 0): array
    {
        $requestData = $this->buildOfferPayload($offerData, $price);

        $redisCache   = cache()->store('redis');
        $rateLimitKey = 'eld_rl_' . self::RATE_LIMIT_KEY_VERSION . '_C_' . $offerId;
        if ($redisCache->get($rateLimitKey)) {
            throw new \RuntimeException('ELD风控限制中，请稍后再试（改价接口在10分钟冷却中）');
        }

        $url   = '/api/v1/currency-management/me/offers';
        $start = microtime(true);

        try {
            $res = $this->http->post($url, [
                'headers' => [
                    'Authorization' => $this->getAccessToken(),
                    // 平台要求该接口使用 json-patch+json，显式声明以覆盖 Guzzle 默认的 application/json
                    'Content-Type'  => 'application/json-patch+json',
                    'Accept'        => '*/*',
                ],
                'json' => $requestData,
            ]);
            $body     = (string) $res->getBody();
            $json     = json_decode($body, true) ?? [];
            $duration = (int) ((microtime(true) - $start) * 1000);

            $statusCode = $res->getStatusCode();
            $success    = $statusCode >= 200 && $statusCode < 300;

            $this->log(
                GameAccountApiLog::TYPE_UPDATE_PRICE,
                $url,
                $requestData,
                $json,
                $success,
                $success ? '' : $this->extractError($json, '改价失败'),
                $duration,
                $gameProductId
            );

            if (!$success) {
                throw new \RuntimeException('Eldorado 改价失败: ' . $this->extractError($json));
            }
            return $json;
        } catch (GuzzleException $e) {
            $duration = (int) ((microtime(true) - $start) * 1000);
            $respJson = null;
            $errMsg   = $e->getMessage();
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $respBody = (string) $e->getResponse()->getBody();
                $respJson = json_decode($respBody, true);
                $errMsg   = $this->extractError($respJson, $e->getMessage());

                if ($e->getResponse()->getStatusCode() === 429) {
                    // 标记该产品 10 分钟冷却；token 按账号共用，同样清掉让下次重新取
                    $redisCache->set($rateLimitKey, 1, self::RATE_LIMIT_TTL);
                    $redisCache->delete('eldorado_access_token_' . $this->account->id);
                }
            }
            $this->log(GameAccountApiLog::TYPE_UPDATE_PRICE, $url, $requestData, $respJson, false, $errMsg, $duration, $gameProductId);
            throw new \RuntimeException('Eldorado 改价失败: ' . $errMsg);
        }
    }

    /**
     * 用 offer_data + 新价格组装整单改价的请求体。
     * 字段形状与平台要求一一对应，缺少关键字段时直接抛异常，避免把空值提交上去覆盖线上配置。
     *
     * @throws \RuntimeException offer_data 不完整时抛出
     */
    protected function buildOfferPayload(array $offerData, float $price): array
    {
        $details                = $offerData['details'] ?? [];
        $pricing                = $details['pricing'] ?? [];
        $augmentedGame          = $offerData['augmentedGame'] ?? [];
        $quantity               = (int) ($pricing['quantity'] ?? 0);
        $minQuantity            = (int) ($pricing['minQuantity'] ?? 0);
        $currency               = (string) ($pricing['pricePerUnit']['currency'] ?? '');
        $volumeDiscounts        = is_array($pricing['volumeDiscounts'] ?? null) ? $pricing['volumeDiscounts'] : [];
        $description            = (string) ($details['description'] ?? '');
        $guaranteedDeliveryTime = (string) ($details['guaranteedDeliveryTime'] ?? '');
        $deliveryMethod         = (string) ($details['deliveryMethod'] ?? '');
        $gameId                 = (string) ($augmentedGame['gameId'] ?? '');
        $category               = (string) ($augmentedGame['category'] ?? '');
        // tradeEnvironmentId 可能是 "0"（无子环境），只能用 === '' 判空，不能用 empty
        $tradeEnvironmentId     = (string) ($augmentedGame['tradeEnvironmentId'] ?? '');
        $offerAttributes        = $this->normalizeOfferAttributes(
            is_array($augmentedGame['offerAttributes'] ?? null) ? $augmentedGame['offerAttributes'] : []
        );

        $missing = [];
        if ($quantity <= 0) {
            $missing[] = 'quantity';
        }
        if ($currency === '') {
            $missing[] = 'currency';
        }
        if ($deliveryMethod === '') {
            $missing[] = 'deliveryMethod';
        }
        if ($gameId === '') {
            $missing[] = 'gameId';
        }
        if ($category === '') {
            $missing[] = 'category';
        }
        if ($tradeEnvironmentId === '') {
            $missing[] = 'tradeEnvironmentId';
        }
        if ($missing) {
            throw new \RuntimeException(
                '线上数据不完整（缺少 ' . implode('、', $missing) . '），请先点「同步线上数据」再改价'
            );
        }

        // 整单覆盖语义：offer_data 里的字段必须原样回传，漏传会把线上对应配置清空。
        // 唯一被替换的是 pricePerUnit.amount。
        return [
            'details' => [
                'description'            => $description,
                'guaranteedDeliveryTime' => $guaranteedDeliveryTime,
                'pricing'                => [
                    'quantity'        => $quantity,
                    'minQuantity'     => $minQuantity,
                    'volumeDiscounts' => $volumeDiscounts,
                    'pricePerUnit'    => [
                        'amount'   => $price,
                        'currency' => $currency,
                    ],
                ],
                'deliveryMethod'         => $deliveryMethod,
            ],
            'augmentedGame' => [
                'gameId'             => $gameId,
                'category'           => $category,
                'tradeEnvironmentId' => $tradeEnvironmentId,
                'offerAttributes'    => $offerAttributes,
            ],
        ];
    }

    /**
     * 归一化 offerAttributes 为平台要求的提交格式（实测 HTTP 201 通过）：
     *   [{"value":"divine-orb","name":"Orbs","id":"path-of-exile-2-orbs",
     *     "type":"Select","display":"NameWithValue"}]
     *
     * 关键点：value 必须是字符串（值 id）。GET 响应里 attributes[].value 是对象
     * {"name":...,"id":...,"imageLocation":...}，直接回传会被平台以
     * "invalid values: value" 拒绝，这里统一降级成 value.id。
     * 外层 name/id/type/display 原样保留，其中 id 是属性定义 id，不是值 id。
     */
    protected function normalizeOfferAttributes(array $attributes): array
    {
        $normalized = [];
        foreach ($attributes as $item) {
            if (!is_array($item)) {
                continue;
            }
            $value = $item['value'] ?? null;
            if (is_array($value)) {
                $valueId = isset($value['id'])
                    ? trim((string) $value['id'])
                    : trim((string) ($value[0]['id'] ?? ''));
            } else {
                $valueId = trim((string) $value);
            }
            // 兼容早期只存了 {"id": 值id} 的旧数据
            if ($valueId === '' && !isset($item['value'])) {
                $valueId = trim((string) ($item['id'] ?? ''));
            }
            if ($valueId === '') {
                continue;
            }
            $normalized[] = [
                'value'   => $valueId,
                'name'    => (string) ($item['name'] ?? ''),
                'id'      => (string) ($item['id'] ?? ''),
                'type'    => (string) ($item['type'] ?? ''),
                'display' => (string) ($item['display'] ?? ''),
            ];
        }
        return $normalized;
    }

    /**
     * 获取线上 offer 详情（同步线上平台数据用）
     *
     * GET /api/v1/currency-management/me/offers/{offerId}
     * 鉴权与改价共用同一个账号级 token（getAccessToken()）。
     *
     * @param string $offerId       Eldorado 平台 offer ID（即 product_id）
     * @param int    $gameProductId 本地产品 ID（仅用于日志关联）
     * @return array 平台原始响应（含 offer / user / userOrderInfo 等）
     * @throws \RuntimeException 获取失败时抛出
     */
    public function getOfferDetail(string $offerId, int $gameProductId = 0): array
    {
        $url   = '/api/v1/currency-management/me/offers/' . $offerId;
        $start = microtime(true);

        try {
            $res = $this->http->get($url, [
                'headers' => [
                    'Authorization' => $this->getAccessToken(),
                    'Accept'        => 'application/json',
                ],
            ]);
            $body     = (string) $res->getBody();
            $json     = json_decode($body, true) ?? [];
            $duration = (int) ((microtime(true) - $start) * 1000);

            $statusCode = $res->getStatusCode();
            $success    = $statusCode >= 200 && $statusCode < 300 && !empty($json['offer']);

            $this->log(
                GameAccountApiLog::TYPE_SYNC_OFFER,
                $url,
                [],
                $json,
                $success,
                $success ? '' : $this->extractError($json, '获取offer详情失败'),
                $duration,
                $gameProductId
            );

            if (!$success) {
                throw new \RuntimeException('Eldorado 获取offer详情失败: ' . $this->extractError($json, '响应中无 offer 数据'));
            }
            return $json;
        } catch (GuzzleException $e) {
            $duration = (int) ((microtime(true) - $start) * 1000);
            $respJson = null;
            $errMsg   = $e->getMessage();
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $respBody = (string) $e->getResponse()->getBody();
                $respJson = json_decode($respBody, true);
                $errMsg   = $this->extractError($respJson, $e->getMessage());
            }
            $this->log(GameAccountApiLog::TYPE_SYNC_OFFER, $url, [], $respJson, false, $errMsg, $duration, $gameProductId);
            throw new \RuntimeException('Eldorado 获取offer详情失败: ' . $errMsg);
        }
    }

    /**
     * 记录调用日志
     */
    private function log(
        string $type,
        string $url,
        array  $requestData,
        ?array $responseData,
        bool   $success,
        string $errorMsg,
        int    $durationMs,
        int    $gameProductId = 0
    ): void {
        GameAccountApiLog::record([
            'game_account_id' => $this->account->id,
            'game_product_id' => $gameProductId,
            'type'            => $type,
            'request_url'     => config('eldorado.base_uri', 'https://www.eldorado.gg') . $url,
            'request_data'    => $requestData,
            'response_data'   => $responseData ? $this->maskSensitive($responseData) : [],
            'status'          => $success ? GameAccountApiLog::STATUS_SUCCESS : GameAccountApiLog::STATUS_FAIL,
            'error_msg'       => mb_substr($errorMsg, 0, 500),
            'duration_ms'     => $durationMs,
            'created_at'      => dateNow(),
        ]);
    }

    /**
     * 从响应体中提取可读错误信息，兼容 message 字符串和 messages 数组两种格式。
     * 429 限流统一返回友好提示。
     */
    private function extractError(?array $json, string $fallback = '未知错误'): string
    {
        if (!$json) {
            return $fallback;
        }
        // 429 限流：统一返回友好提示
        if (($json['code'] ?? 0) === 429) {
            return 'ELD改价太频繁，请10分钟后尝试';
        }
        // messages 数组格式
        if (!empty($json['messages']) && is_array($json['messages'])) {
            $msg = $json['messages'][0] ?? '';
            return is_string($msg) ? $msg : ($msg['text'] ?? $fallback);
        }
        // message 字符串格式
        if (!empty($json['message']) && is_string($json['message'])) {
            return $json['message'];
        }
        return $fallback;
    }

    /**
     * 递归脱敏：token / secret 类字段只保留首尾各 4 位；跳过非字符串 key（数字索引数组）
     */
    private function maskSensitive(array $data): array
    {
        $sensitiveKeys = ['accesstoken', 'client_secret', 'clientsecret', 'authorization'];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskSensitive($value);
            } elseif (is_string($key) && is_string($value) && in_array(strtolower($key), $sensitiveKeys, true)) {
                $data[$key] = $this->maskToken($value);
            }
        }
        return $data;
    }

    private function maskToken(string $token): string
    {
        $len = strlen($token);
        if ($len <= 8) {
            return str_repeat('*', $len);
        }
        return substr($token, 0, 4) . '***' . substr($token, -4);
    }
}
