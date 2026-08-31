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
 * 改价（两个接口有独立限额，各自冷却 10 分钟，两个都冷却中则直接跳过）：
 *   PUT /api/predefinedOffersUser/me/{offerId}/changePrice        [接口 A]
 *   PUT /api/v1/currency-management/me/offers/{offerId}/change-price [接口 B]
 *   header: Authorization: {tokenType} {accessToken}
 *   body JSON: {"amount": 0.04, "currency": "USD"}
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
