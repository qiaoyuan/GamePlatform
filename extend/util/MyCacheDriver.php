<?php

namespace util;

use Psr\SimpleCache\CacheInterface;
use think\facade\Cache;

class MyCacheDriver implements CacheInterface
{

    public function get($key, $default = null)
    {
        return Cache::get($key, $default);
    }

    public function set($key, $value, $ttl = null)
    {
        return Cache::set($key, $value, $ttl);
    }

    public function delete($key)
    {
        return Cache::delete($key);
    }

    public function clear()
    {
        return Cache::clear();
    }

    public function getMultiple($keys, $default = null)
    {
        return Cache::getMultiple($keys, $default);
    }

    public function setMultiple($values, $ttl = null)
    {
        return Cache::setMultiple($values, $ttl);
    }

    public function deleteMultiple($keys)
    {
        return Cache::deleteMultiple($keys);
    }

    public function has($key)
    {
        return Cache::has($key);
    }
}