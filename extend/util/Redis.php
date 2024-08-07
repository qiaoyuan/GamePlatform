<?php

namespace util;

/**
 * Redis缓存驱动
 *
 * @method array keys($pattern)
 * @method array lRange($key, $start, $end)
 * @method int lRem($key, $value, $count)
 * @method int sAdd($key, $value1, $value2 = null, $valueN = null)
 * @method mixed lPop($key)
 * @method int|false lPush($key, ...$value1)
 * @method mixed rPop($key)
 * @method array lTrim($key, $start, $stop)
 * @method array blPop(array $keys, $timeout)
 * @method array brPop(array $keys, $timeout)
 * @method bool expire($key, $ttl)
 * @method mixed get($key)
 * @method bool set($key, $value, $timeout = null)
 * @method bool setex($key, $ttl, $value)
 * @method int del($key1, ...$otherKeys)
 * @method int|bool hSet($key, $hashKey, $value)
 * @method int|bool hSetNx($key, $hashKey, $value)
 * @method string|false hGet($key, $hashKey)
 * @method int|bool hDel($key, $hashKey1, ...$otherHashKeys)
 * @method array hKeys($key)
 * @method array hVals($key)
 * @method array hGetAll($key)
 * @method bool hExists($key, $hashKey)
 * @method int hIncrBy($key, $hashKey, $value)
 * @method bool hMSet($key, $hashKeys)
 * @method array hMGet($key, $hashKeys)
 * @method array mget(array $array)
 * @method bool mset(array $array)
 * @method bool flushDB()
 */
class Redis
{
    const DB_CHROME_EXT = 4;
    const DB_CACHE = 0;
    const DB_SESSION = 3;
    const DB_DAILY = 1;
    const DB_USER = 2;

    const DATA = 1;

    public ?\Redis $handler = null;

    public bool $isClose = false;

    protected $options = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'timeout' => false,
        'persistent' => false,
        'length' => 0,
        'prefix' => '',
    ];

    protected int $type = 0;

    /**
     * 架构函数
     * @param int $type 缓存分类 0 = content 1 = comment 2 = list
     * @param array $options
     * @access public
     */
    public function __construct(int $type = 0, array $options = [])
    {
        $this->options = array_merge($this->options, config('redis'), $options);
        if (isset($this->options['isClose']) && $this->options['isClose']) {
            $this->isClose = true;
        }
        $func = $this->options['persistent'] ? 'pconnect' : 'connect';
        $this->handler = new \Redis;
        $this->handler->$func($this->options['host'], $this->options['port']);
        if ('' != $this->options['password']) {
            $this->handler->auth($this->options['password']);
        }
        $this->type = $type;
        if ($this->type) {
            $this->handler->select($type);
        }
    }

    /**
     * 清除缓存
     * @access public
     * @return boolean
     */
    public function clear(): bool
    {
        if ($this->isClose) {
            return false;
        }
        return $this->handler->flushDB();
    }

    public function select($type): Redis
    {
        $this->handler->select($type);
        return $this;
    }

    public function __call($name, $arguments)
    {
        try {
            if ($this->isClose) {
                return false;
            }
            return $this->handler->$name(...$arguments);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
