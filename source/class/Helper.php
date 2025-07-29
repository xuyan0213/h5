<?php

namespace Gac163202505;

use Generator;

class Helper
{
    private static string $aesKey = 'i!n2s#u$n%H^5&';

    private static string $aesIv = 'insunH5';

    private static int $exp = 432000;

    /**
     * 生成唯一的openid
     * @param string $prefix
     * @return string
     */
    public static function generateUnionId(string $prefix = 'unionid_'): string
    {
        return md5(uniqid($prefix, true) . microtime());
    }

    /**
     * 记录用户操作日志
     * @param $userId
     * @param $db
     * @return void
     */
    public static function apiLog($userId, $db): void
    {
        $data = [
            'user_id'    => $userId,
            'method'     => $_SERVER['REQUEST_METHOD'],
            'path'       => $_SERVER['REQUEST_URI'],
            'ip'         => getUserIP(),
            'params'     => json_encode($_GET, JSON_UNESCAPED_UNICODE),
            'data'       => json_encode($_POST, JSON_UNESCAPED_UNICODE),
            'referer'    => $_SERVER['HTTP_REFERER'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $db->insert('api_log', $data);
    }

    /**
     * 验签
     * @param array $data
     * @return void
     */
    public static function checkSign(array $data): void
    {
        global $allow_origin;
        global $env;
        global $redis;
        global $redisPrefix;
        $error['post'] = $data;
        $error['sign'] = $_SERVER['HTTP_SIGNATURE'];
        if (!empty($env) && $env == 'prod') {
            if (!empty($_SERVER['HTTP_REFERER'])) {
                if (!in_array(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST), $allow_origin)) {
                    $redis->hSet($redisPrefix . "error_post_504", time(), json_encode($error, JSON_UNESCAPED_UNICODE));
                    jsonReturn(false, 504, '非法请求');
                }
            } else {
                $redis->hSet($redisPrefix . "error_post_506", time(), json_encode($error, JSON_UNESCAPED_UNICODE));
                jsonReturn(false, 506, '非法请求');
            }
        }
        //验签
        $signObj = new Sign();
        $signRes = $signObj->checkSign($data);
        if (!$signRes) {
            $redis->hSet($redisPrefix . "error_post_505", time(), json_encode($error, JSON_UNESCAPED_UNICODE));
            jsonReturn(false, 505, '非法请求');
        }
    }

    /**
     * 获取活动状态
     * @param $startTime
     * @param $endTime
     * @param string $type
     * @return void
     */
    public static function getStatus($startTime, $endTime, string $type = '活动'): void
    {
        if (time() < strtotime($startTime)) {
            jsonReturn(false, 300, $type . '未开始！');
        } elseif (time() > strtotime($endTime)) {
            jsonReturn(false, 301, $type . '已结束！');
        }
    }

    /**
     * 实物抽奖
     * @param $userId int 用户ID
     * @param $date string 队列日期
     * @return mixed
     */
    public static function physicalLottery(int $userId, string $date): mixed
    {
        global $redis;
        $luckier = Constant::KEY_LUCKIER;

        $lotteryList = Constant::KEY_LOTTERY_PHYSICAL_LIST . $date;
        $lua         = <<<SCRIPT
        local isLuckier = redis.call("sismember", KEYS[1], ARGV[1]);
        local pid = redis.call("rpop",KEYS[2])
        if (isLuckier ~= 1 and pid ~= false) then
            redis.call("sadd", KEYS[1], ARGV[1])
            return pid
        else
            return nil
        end
SCRIPT;
        //对应的redis命令如下 eval "return {KEYS[1],KEYS[2],ARGV[1],ARGV[2]}" 2 key1 key2 first second
        return $redis->eval($lua, [$luckier, $lotteryList, $userId], 2);
    }

    /**
     * 排行榜抽奖
     * @param $userId int 用户ID
     * @return mixed
     */
    public static function rankLottery(int $userId): mixed
    {
        global $redis;
        $luckier = Constant::KEY_LUCKIER_RANK;

        $lotteryList = Constant::KEY_LOTTERY_RANK_LIST;
        $lua         = <<<SCRIPT
        local isLuckier = redis.call("sismember", KEYS[1], ARGV[1]);
        local pid = redis.call("rpop",KEYS[2])
        if (isLuckier ~= 1 and pid ~= false) then
            redis.call("sadd", KEYS[1], ARGV[1])
            return pid
        else
            return nil
        end
SCRIPT;
        //对应的redis命令如下 eval "return {KEYS[1],KEYS[2],ARGV[1],ARGV[2]}" 2 key1 key2 first second
        return $redis->eval($lua, [$luckier, $lotteryList, $userId], 2);
    }


    /**
     * 检查IP是否在黑名单内
     * @return bool
     */
    public static function ipBlackList(): bool
    {
        global $redis;
        $ip = getUserIP();
        return $redis->sIsMember(Constant::KEY_IP_BLACKLIST, $ip);
    }

    /**
     * 添加IP到黑名单
     * @return void
     */
    public static function addIpBlackList(): void
    {
        global $redis;
        $ip = getUserIP();
        $redis->sAdd(Constant::KEY_IP_BLACKLIST, $ip);
    }

    /**
     * 记录用户抽奖
     * @param $userId int 用户ID
     * @param $status int 抽奖状态
     * @param int $type 1:普通抽奖 2:每日抽奖 3:排行榜抽奖
     * @return void
     */
    public static function lotteryRecord(int $userId, int $type, int $status = 200): void
    {
        global $redis;
        global $db;
        match ($type) {
            1 => $redisKey = Constant::KEY_LOTTERY,
            2 => $redisKey = Constant::KEY_LOTTERY_DAY . date('Y-m-d'),
            3 => $redisKey = Constant::KEY_LOTTERY_RANK
        };
        $redis->sAdd($redisKey, $userId);
        //记录抽奖记录
        $data = [
            'user_id'    => $userId,
            'ip'         => getUserIP(),
            'type'       => $type,
            'status'     => $status . '=>' . Constant::LOTTERY_STATUS_LABEL[$status],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $db->insert('prize_record', $data);
        $db->update('user', ['num[+]' => 1], ['id' => $userId]);
    }


    /**
     * 获取Token值
     * @param array $data 数据
     * @param int $exp 过期时间(秒)默认5天
     * @return bool|string
     */
    public static function getToken(array $data, int $exp = 432000): bool|string
    {
        $json    = json_encode($data, JSON_UNESCAPED_UNICODE);
        $jwt     = new Jwt();
        $aes     = new Aes(self::$aesKey, substr(md5(self::$aesIv), -16));
        $json    = $aes->encrypt($json);
        $payload = [
            'iat' => time(),
            'exp' => time() + $exp,
            'nbf' => time(),
            'sub' => $_SERVER['HTTP_HOST'],
            'jti' => md5(uniqid('JWT') . time()),
            'h5'  => $json,
        ];
        return $jwt::getToken($payload);
    }

    /**
     * 验证token并解密用户信息
     * @param string $token
     * @return bool|mixed|string
     */
    public static function verifyToken(string $token = ''): mixed
    {
        $token = $token ?: self::getRequestToken();
        if (!$token) {
            return false;
        }
        $jwt  = new Jwt();
        $info = $jwt::verifyToken($token);
        if (!$info) {
            return false;
        }
        $aes  = new Aes(self::$aesKey, substr(md5(self::$aesIv), -16));
        $json = $aes->decrypt($info['h5']);
        unset($info['h5']);
        return $info + json_decode($json, true);
    }

    /**
     * 获取请求头中的token
     * @return false|string
     */
    public static function getRequestToken(): bool|string
    {
        if (empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return false;
        }
        $header = $_SERVER['HTTP_AUTHORIZATION'];
        $method = 'bearer';
        //去除token中可能存在的bearer标识
        return trim(str_ireplace($method, '', $header));
    }

    /**
     * 返回token
     * @param $token
     * @return false|string
     */
    public static function respondWithToken($token): bool|string
    {
        $data = [
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => 60 * 60 * 24 * 5
        ];
        return json_encode(['result' => true, 'code' => 200, 'data' => $data]);
    }

    public static function exportPrizeCSV($header, $where, $filename): void
    {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . $filename . '.csv');
        header('Cache-Control: max-age=0');
        $fp = fopen('php://output', 'w');
        fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($fp, $header);
        foreach (self::dataGenerator($where) as $value) {
            fputcsv($fp, $value);
        }
        fclose($fp);
    }

    /**
     * 导出中奖记录
     * @param $where
     * @return Generator
     */
    public static function dataGenerator($where): Generator
    {
        global $db;
        $offset = 0;
        $limit  = 1000; // 每次查询1000条数据
        while (true) {
            $rows = $db->select("prize", [
                'id',
                'nickname',
                'name',
                'phone',
                'pid',
                'prizename',
                'province',
                'city',
                'address',
                'created_at'
            ], array_merge($where, ['LIMIT' => [$offset, $limit]]));
            if (empty($rows)) {
                break;
            }
            foreach ($rows as $row) {
                yield [
                    $row['id'],
                    $row['nickname'],
                    $row['name'],
                    $row['phone'],
                    $row['pid'],
                    $row['prizename'],
                    in_array($row['pid'], array_keys(Constant::LOTTERY_LIST)) ? '普通奖品' : '排行榜奖品',
                    $row['province'],
                    $row['city'],
                    $row['address'],
                    $row['created_at']
                ];
            }
            $offset += $limit;
        }
    }

    /**
     * 获取最早的队列
     * @param $queueList
     * @return string
     */
    public static function getEarliestQueue($queueList): string
    {

        $earliest = date('Y-m-d');
        if (empty($queueList)) {
            return $earliest;
        }
        foreach ($queueList as $queue) {
            //去掉前缀,获取日期部分 只需要最后一个:的后面的部分
            $queue = substr($queue, strrpos($queue, ':') + 1);
            if (strtotime($queue) < strtotime($earliest)) {
                $earliest = $queue;
            }
        }
        return $earliest;
    }

    /**
     * 把分数加上时间戳,方便排序
     * @param $score
     * @return float
     */
    public static function scoreAddTime($score): float
    {
        $baseTime = strtotime('2025-01-01');
        $float    = $baseTime - time();
        return (float)($score . '.' . $float);
    }

    /**
     * 加密
     * @param string $data
     * @return string
     */
    public static function encrypt(string $data): string
    {
        $aes = new Aes(substr(md5(self::$aesKey), 0, 16), substr(md5(self::$aesKey), -16));
        return $aes->encrypt($data);
    }

    /**
     * 解密
     * @param string $data
     * @return string
     */
    public static function decrypt(string $data): string
    {
        $aes = new Aes(substr(md5(self::$aesKey), 0, 16), substr(md5(self::$aesKey), -16));
        return $aes->decrypt($data);
    }
}