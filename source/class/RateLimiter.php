<?php

namespace Gac163202505;
/**
 * 限流控制
 */
class RateLimiter
{
    private $minNum;    //单个IP每分访问数
    private $dayNum;    //单个IP每天总的访问量

    private $redis;

    private $redisPrefix;

    public function __construct($redis, $redisPrefix, $dayNum, $minNum)
    {
        $this->redis       = $redis;
        $this->dayNum      = $dayNum;
        $this->minNum      = $minNum;
        $this->redisPrefix = $redisPrefix;

    }

    /**
     * 限流
     * @param $uid
     * @return bool
     */
    public function allowRequest($uid): bool
    {
        $minNumKey = $this->redisPrefix . 'limit_min:' . $uid;
        $dayNumKey = $this->redisPrefix . 'limit_day:' . $uid;
        $resMin    = $this->getRedis($minNumKey, $this->minNum, 60);
        $resDay    = $this->getRedis($dayNumKey, $this->dayNum, 86400);
        if (!$resMin || !$resDay) {
            return false;
        }
        return true;
    }

    /**
     * 获取redis
     * @param $key
     * @param $initNum
     * @param $expire
     * @return bool
     */
    public function getRedis($key, $initNum, $expire): bool
    {
        $time = time();
        $this->redis->watch($key);
        $limitVal = $this->redis->get($key);
        if ($limitVal) {
            $limitVal = json_decode($limitVal, true);
            $newNum   = min($initNum, ($limitVal['num'] - 1) + (($initNum / $expire) * ($time - $limitVal['time'])));
            if ($newNum > 0) {
                $redisVal = json_encode(['num' => $newNum, 'time' => time()]);
            } else {
                return false;
            }
        } else {
            $redisVal = json_encode(['num' => $initNum, 'time' => time()]);
        }
        $this->redis->multi();
        $this->redis->set($key, $redisVal);
        $rob_result = $this->redis->exec();
        if (!$rob_result) {
            return false;
        }
        return true;
    }
}