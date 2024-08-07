<?php

namespace util;

class Data
{
    protected static ?Data $instance = null;
    
    private array $data = [];

    /**
     * @access public
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * @access public
     * @param  string|array  $name
     * @param  mixed         $value
     * @return mixed
     */
    public function set($name, $value = null)
    {
        if (is_string($name)) {
            $this->data[$name] = $value;
            return $value;
        } elseif (is_array($name)) {
            $result = $this->data = array_merge($this->data, $name);
        } else {
            // 为空直接返回 已有配置
            $result = $this->data;
        }

        return $result;
    }

    /**
     * 移除配置
     * @access public
     * @param  string  $name 配置参数名（支持三级配置 .号分割）
     * @return void
     */
    public function remove($name)
    {
        if (isset($this->data[$name])) {
            unset($this->data[$name]);
        }
    }

    public function get($name)
    {
        return $this->data[$name] ?? null;
    }
}
