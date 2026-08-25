<?php

namespace app\common\service;

use app\common\model\GameAccount;
use app\common\model\GameAccountApiLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * G2G 第三方平台 API 客户端
 *
 * 职责：
 * 1. 用账号的 refresh_token/active_device_token/long_lived_token 换取 access_token，并缓存（默认10分钟）。
 * 2. 用 access_token 调用改价等业务接口。
 * 3. 每次外部调用都落一条日志（app/common/model/GameAccountApiLog），敏感字段做脱敏处理。
 *
 * 使用方式：
 *   $client = new G2gClient($gameAccount);
 *   $client->updatePrice($productId, $price);
 */
class G2gClient
{
    private GameAccount $account;

    private Client $http;

    public function __construct(GameAccount $account)
    {
        $this->account = $account;
        $this->http = new Client([
            'base_uri' => config('g2g.base_uri'),
            'timeout' => config('g2g.timeout', 10),
        ]);
    }

    /**
     * 获取 access_token（有缓存优先用缓存，缓存时长见 config('g2g.token_cache_ttl')）
     *
     * @throws \RuntimeException 刷新失败时抛出
     */
    public function getAccessToken(): string
    {
        $cacheKey = 'g2g_access_token_' . $this->account->id;
        $token = cache('redis')->get($cacheKey);
        if ($token) {
            return $token;
        }
        $token = $this->refreshAccessToken();
        cache('redis')->set($cacheKey, $token, config('g2g.token_cache_ttl', 600));
        return $token;
    }

    /**
     * 强制刷新 access_token（不读缓存），并记录调用日志
     */
    public function refreshAccessToken(): string
    {
        $requestData = [
            'user_id' => $this->account->user_id,
            'refresh_token' => $this->account->refresh_token,
            'active_device_token' => $this->account->active_device_token,
            'long_lived_token' => $this->account->long_lived_token,
        ];
        $start = microtime(true);
        $url = '/user/refresh_access';
        try {
            $res = $this->http->post($url, ['json' => $requestData]);
            $body = (string)$res->getBody();
            $json = json_decode($body, true);
            $duration = (int)((microtime(true) - $start) * 1000);
            $accessToken = $json['payload']['access_token'] ?? '';
            $success = $accessToken !== '' && ($json['code'] ?? null) == 2000;
            $this->log(
                GameAccountApiLog::TYPE_REFRESH_TOKEN,
                $url,
                $requestData,
                $json,
                $success,
                $success ? '' : ($json['messages'][0] ?? '刷新令牌失败'),
                $duration
            );
            if (!$success) {
                throw new \RuntimeException('G2G令牌刷新失败: ' . ($json['messages'][0] ?? '未知错误'));
            }
            return $accessToken;
        } catch (GuzzleException $e) {
            $duration = (int)((microtime(true) - $start) * 1000);
            $this->log(GameAccountApiLog::TYPE_REFRESH_TOKEN, $url, $requestData, null, false, $e->getMessage(), $duration);
            throw new \RuntimeException('G2G令牌刷新请求异常: ' . $e->getMessage());
        }
    }

    /**
     * 改价
     *
     * @param string $productId G2G 平台产品编号
     * @param float $price 新价格
     * @param int $gameProductId 本地产品id（仅用于日志关联，非0时记录）
     * @throws \RuntimeException 改价失败时抛出，调用方需捕获并提示用户
     */
    public function updatePrice(string $productId, float $price, int $gameProductId = 0): array
    {
        $accessToken = $this->getAccessToken();
        $requestData = [
            'unit_price' => $price,
            'seller_id' => $this->account->user_id,
        ];
        $start = microtime(true);
        $url = '/offer/' . $productId . '?v=v2';
        try {
            $res = $this->http->put($url, [
                'headers' => [
                    'authorization' => $accessToken,
                    'content-type' => 'application/json',
                    'origin' => config('g2g.site_uri'),
                    'referer' => config('g2g.site_uri') . '/',
                ],
                'json' => $requestData,
            ]);
            $body = (string)$res->getBody();
            $json = json_decode($body, true);
            $duration = (int)((microtime(true) - $start) * 1000);
            $success = ($json['code'] ?? null) == 2000;
            $this->log(
                GameAccountApiLog::TYPE_UPDATE_PRICE,
                $url,
                $requestData,
                $json,
                $success,
                $success ? '' : ($json['messages'][0] ?? '改价失败'),
                $duration,
                $gameProductId
            );
            if (!$success) {
                throw new \RuntimeException('G2G改价失败: ' . ($json['messages'][0] ?? '未知错误'));
            }
            return $json;
        } catch (GuzzleException $e) {
            $duration = (int)((microtime(true) - $start) * 1000);
            // Guzzle 默认把响应体截断到 120 字，这里从异常里取完整响应体，提取 G2G 的具体校验信息
            $respJson = null;
            $errMsg = $e->getMessage();
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $respBody = (string) $e->getResponse()->getBody();
                $respJson = json_decode($respBody, true);
                $g2gMsg = $respJson['messages'][0]['text'] ?? ($respJson['messages'][0] ?? $respBody);
                $errMsg = is_string($g2gMsg) ? $g2gMsg : json_encode($g2gMsg, JSON_UNESCAPED_UNICODE);
            }
            $this->log(GameAccountApiLog::TYPE_UPDATE_PRICE, $url, $requestData, $respJson, false, $errMsg, $duration, $gameProductId);
            throw new \RuntimeException('G2G改价失败: ' . $errMsg);
        }
    }

    /**
     * 记录调用日志，敏感字段（令牌类）脱敏后落库
     */
    private function log(
        string $type,
        string $url,
        array $requestData,
        ?array $responseData,
        bool $success,
        string $errorMsg,
        int $durationMs,
        int $gameProductId = 0
    ): void {
        GameAccountApiLog::record([
            'game_account_id' => $this->account->id,
            'game_product_id' => $gameProductId,
            'type' => $type,
            'request_url' => config('g2g.base_uri') . $url,
            'request_data' => $this->maskSensitive($requestData),
            'response_data' => $responseData ? $this->maskSensitive($responseData) : [],
            'status' => $success ? GameAccountApiLog::STATUS_SUCCESS : GameAccountApiLog::STATUS_FAIL,
            'error_msg' => mb_substr($errorMsg, 0, 500),
            'duration_ms' => $durationMs,
            'created_at' => dateNow(),
        ]);
    }

    /**
     * 递归脱敏：令牌类字段只保留首尾各4位，中间用 *** 遮盖
     */
    private function maskSensitive(array $data): array
    {
        $sensitiveKeys = [
            'refresh_token', 'active_device_token', 'long_lived_token',
            'access_token', 'authorization',
        ];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskSensitive($value);
            } elseif (is_string($value) && in_array($key, $sensitiveKeys, true)) {
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
