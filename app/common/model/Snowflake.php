<?php

namespace app\common\model;

use Exception;

class Snowflake
{
    const EPOCH = 1609459200000; // 定义一个纪元时间 (2021-01-01 00:00:00 UTC)
    private $dataCenterId;
    private $workerId;
    private $sequence;
    private $lastTimestamp;

    public function __construct($dataCenterId = 0, $workerId = 0)
    {
        $this->dataCenterId = $dataCenterId;
        $this->workerId = $workerId;
        $this->sequence = 0;
        $this->lastTimestamp = -1;
    }

    private function timeGen()
    {
        return floor(microtime(true) * 1000);
    }

    private function tilNextMillis($lastTimestamp)
    {
        $timestamp = $this->timeGen();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->timeGen();
        }
        return $timestamp;
    }

    public function nextId()
    {
        $timestamp = $this->timeGen();

        if ($timestamp < $this->lastTimestamp) {
            throw new Exception("Clock moved backwards. Refusing to generate id for " . ($this->lastTimestamp - $timestamp) . " milliseconds");
        }

        if ($timestamp == $this->lastTimestamp) {
            $this->sequence = ($this->sequence + 1) & 4095;
            if ($this->sequence == 0) {
                $timestamp = $this->tilNextMillis($this->lastTimestamp);
            }
        } else {
            $this->sequence = 0;
        }

        $this->lastTimestamp = $timestamp;

        return (($timestamp - self::EPOCH) << 22) |
               ($this->dataCenterId << 17) |
               ($this->workerId << 12) |
               $this->sequence;
    }
}


