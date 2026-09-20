<?php
declare(strict_types=1);

namespace app\common\service;

/** 按爬取目标串行消费通知；产品 A/B/C 冷却仍按平台 product_id 隔离。 */
final class PriceStrategyTargetLease
{
    private function __construct(
        private object $redis,
        private string $key,
        private string $token,
        private int $ttl
    ) {
    }

    public static function acquire(object $redis, string $key, int $ttl = 900): ?self
    {
        $token = bin2hex(random_bytes(16));
        if (!$redis->set($key, $token, ['nx', 'ex' => $ttl])) {
            return null;
        }
        return new self($redis, $key, $token, $ttl);
    }

    public function renew(): void
    {
        try {
            $renewed = $this->redis->eval(
                'if redis.call("get", KEYS[1]) == ARGV[1] then return redis.call("expire", KEYS[1], ARGV[2]) else return 0 end',
                [$this->key, $this->token, $this->ttl],
                1
            );
        } catch (\Throwable $e) {
            throw new PriceStrategyLeaseLostException('无法续租爬取目标处理锁，停止本轮改价', 0, $e);
        }
        if ((int) $renewed !== 1) {
            throw new PriceStrategyLeaseLostException('爬取目标处理锁已失效，停止本轮改价');
        }
    }

    public function release(): void
    {
        $this->redis->eval(
            'if redis.call("get", KEYS[1]) == ARGV[1] then return redis.call("del", KEYS[1]) else return 0 end',
            [$this->key, $this->token],
            1
        );
    }
}
