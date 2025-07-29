<?php

use GacBd202411\Helper;
use GacBd202411\Constant;

$ac  = getgpc('ac');
$mod = getgpc('mod');
$ip  = getip();
//验签
Helper::checkSign(getgpc());
$today = date('Y-m-d');

switch ($ac) {
    case 'draw':
        try {
            //判断活动状态
            Helper::getStatus($startTime, $endTime);
            //限流
            $lockKey = Constant::KEY_LOTTERY_LOCK . $userId;
            if ($redis->get($lockKey)) {
                jsonReturn(false, 603, '点太快了,慢一点~');
            }
            $redis->set($lockKey, 1, array('nx', 'ex' => 2));
            //判断用户抽奖类型
            $type = $redis->sIsmember(Constant::KEY_LOTTERY, $userId) ? 2 : 1;

            //获取抽奖次数
            //1. 判断是否有资格
            if ($type == 1) {
                //如果是第一次抽奖.则需要先完成游戏
                $game = $redis->sIsmember(Constant::KEY_GAME, $userId);
                if (!$game) {
                    jsonReturn(false, 601, '还没有抽奖资格,请先完成游戏');
                }
            } else {
                //每天都可以抽一次奖品,与首次抽奖无关
                $times = $redis->sIsmember(Constant::KEY_LOTTERY_DAY . date('Y-m-d'), $userId);
                //如果今天抽过奖了,或者没有预约过,则不能抽奖
                if ($times || !$redis->sIsmember(Constant::KEY_APPOINTMENT, $userId)) {
                    jsonReturn(false, 601, '今日抽奖次数已用完');
                }
            }
            /*****************抽奖******************/


            //已中奖用户
            $luckierKey = Constant::KEY_LUCKIER;
            $isLuckier  = $redis->sIsmember($luckierKey, $userId);
            if ($isLuckier) {
                Helper::lotteryRecord($userId, $type, Constant::LOTTERY_STATUS['WIN']);
                jsonReturn(true, Constant::LOTTERY_STATUS['WIN'], '操作成功');
            }
            //IP黑名单
            if (Helper::ipBlackList() && $lotteryBlackOpen) {
                Helper::lotteryRecord($userId, $type, Constant::LOTTERY_STATUS['IP_BLACKLIST']);
                jsonReturn(true, Constant::LOTTERY_STATUS['IP_BLACKLIST'], '操作成功');
            }

            //查询今日奖池是否还有奖品
            $leftPrizeCount = $redis->lLen(Constant::KEY_LOTTERY_PHYSICAL_LIST . date('Y-m-d'));
            if ($leftPrizeCount <= 0) {
                Helper::lotteryRecord($userId, $type, Constant::LOTTERY_STATUS['EMPTY']);
                jsonReturn(true, Constant::LOTTERY_STATUS['EMPTY'], '操作成功');
            }

            //中奖记录
            $record = [
                'user_id'    => $userId,
                'ip'         => $ip,
                'nickname'   => $nickname ?? '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            /*******start***抽奖***start******/
            $PA  = $redis->get(Constant::KEY_LOTTERY_RATE[0]);             //实物奖品概率
            $PB  = $redis->get(Constant::KEY_LOTTERY_RATE[1]);             //未中奖概率
            $arr = array(
                '1' => $PA,
                '2' => $PB,
            );
            $get = get_rand($arr);
            if ($get == 2) {
                Helper::lotteryRecord($userId, $type, Constant::LOTTERY_STATUS['LOSE']);
                jsonReturn(true, Constant::LOTTERY_STATUS['LOSE'], '操作成功');
            }
            if ($get == 1) {
                $queueList = $redis->keys(Constant::KEY_LOTTERY_PHYSICAL_LIST . '*');
                $queueDate = Helper::getEarliestQueue($queueList);
                $pid       = Helper::physicalLottery($userId, $queueDate);
            }
            if (empty($pid)) {
                Helper::lotteryRecord($userId, $type, Constant::LOTTERY_STATUS['NO_PRIZE']);
                jsonReturn(true, Constant::LOTTERY_STATUS['NO_PRIZE'], '操作成功');
                break;
            }
            $record['pid']       = $pid;
            $record['prizename'] = Constant::LOTTERY_LIST[$pid];
            $record['date']      = $queueDate ?? $today;

            //查询用户是否已填写过中奖信息
            $prizeInfo = $db->get('prize', '*', ['user_id' => $userId]);
            if (!empty($prizeInfo) && !empty($prizeInfo['name'])) {
                $record['name']     = $prizeInfo['name'];
                $record['phone']    = $prizeInfo['phone'];
                $record['province'] = $prizeInfo['province'];
                $record['city']     = $prizeInfo['city'];
                $record['address']  = $prizeInfo['address'];
            }
            //写入MySQL记录
            $result = $db->insert('prize', $record);
            if (!$result->rowCount()) {
                //放入奖品队列
                $redis->lPush(Constant::KEY_LOTTERY_PHYSICAL_LIST . $today, $pid);
                Helper::lotteryRecord($userId, $type, Constant::LOTTERY_STATUS['DB_FAIL']);
                jsonReturn(true, Constant::LOTTERY_STATUS['DB_FAIL'], '操作成功');
            }
            //写入Redis记录
            $redis->lPush(Constant::KEY_LOTTERY_RECORD, json_encode($record, JSON_UNESCAPED_UNICODE));
            Helper::lotteryRecord($userId, $type);
            jsonReturn(true, 200, '操作成功', ['pid' => $pid, 'prizename' => Constant::LOTTERY_LIST[$pid]]);
        } catch (Exception $e) {
            jsonReturn(false, $e->getCode(), $e->getMessage());
        }
        break;

    case 'draw_rank':
        //判断活动状态
        Helper::getStatus($startRankTime, $endRankTime, '抽奖时间');
        //限流
        $lockKey = Constant::KEY_LOTTERY_LOCK . $userId;
        if ($redis->get($lockKey)) {
            jsonReturn(false, 603, '点太快了,慢一点~');
        }
        $redis->set($lockKey, 1, array('nx', 'ex' => 2));
        //判断用户是否有抽奖资格
        $rank = $redis->zRevRank(Constant::KEY_SCORE_RANK, $userId);
        if ($rank === false || $rank > Constant::LOTTERY_RANK) {
            jsonReturn(false, 601, '没有抽奖资格');
        }
        if ($redis->sIsmember(Constant::KEY_LOTTERY_RANK, $userId)) {
            jsonReturn(false, 602, '抽奖次数已用完');
        }

        //中奖记录
        $record = [
            'user_id'    => $userId,
            'ip'         => $ip,
            'nickname'   => $nickname ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        /*******start***抽奖***start****/
        //已中奖用户
        $luckierKey = Constant::KEY_LUCKIER_RANK;
        $isLuckier  = $redis->sIsmember($luckierKey, $userId);
        if ($isLuckier) {
            Helper::lotteryRecord($userId, 3, Constant::LOTTERY_STATUS['WIN']);
            jsonReturn(true, Constant::LOTTERY_STATUS['WIN'], '操作成功');
        }
        //IP黑名单
        if (Helper::ipBlackList() && $lotteryBlackOpen) {
            Helper::lotteryRecord($userId, 3, Constant::LOTTERY_STATUS['IP_BLACKLIST']);
            jsonReturn(true, Constant::LOTTERY_STATUS['IP_BLACKLIST'], '操作成功');
        }
        //
        $PA  = $redis->get(Constant::KEY_LOTTERY_RANK_RATE[0]);      //实物奖品概率
        $PB  = $redis->get(Constant::KEY_LOTTERY_RANK_RATE[1]);      //未中奖概率
        $arr = array(
            '1' => $PA,
            '2' => $PB,
        );
        $get = get_rand($arr);  //根据概率获取奖项id
        if ($get == 2) {
            Helper::lotteryRecord($userId, 3, Constant::LOTTERY_STATUS['LOSE']);
            jsonReturn(true, Constant::LOTTERY_STATUS['LOSE'], '操作成功');
        }
        if ($get == 1) {
            $pid = Helper::rankLottery($userId);
        }
        if (empty($pid)) {
            Helper::lotteryRecord($userId, 3, Constant::LOTTERY_STATUS['NO_PRIZE']);
            jsonReturn(true, Constant::LOTTERY_STATUS['NO_PRIZE'], '操作成功');
            break;
        }
        $record['pid']       = $pid;
        $record['prizename'] = Constant::LOTTERY_RANK_LIST[$pid];
        $record['date']      = $queueDate ?? $today;
        //查询用户是否已填写过中奖信息
        $prizeInfo = $db->get('prize', '*', ['user_id' => $userId]);
        if (!empty($prizeInfo) && !empty($prizeInfo['name'])) {
            $record['name']     = $prizeInfo['name'];
            $record['phone']    = $prizeInfo['phone'];
            $record['province'] = $prizeInfo['province'];
            $record['city']     = $prizeInfo['city'];
            $record['address']  = $prizeInfo['address'];
        }
        //写入MySQL记录
        $result = $db->insert('prize', $record);
        if (!$result->rowCount()) {
            //放入奖品队列
            $redis->lPush(Constant::KEY_LOTTERY_RANK_LIST . $today, $pid);
            Helper::lotteryRecord($userId, 3, Constant::LOTTERY_STATUS['DB_FAIL']);
            jsonReturn(true, Constant::LOTTERY_STATUS['DB_FAIL'], '操作成功');
        }
        //写入Redis记录
        $redis->lPush(Constant::KEY_LOTTERY_RECORD, json_encode($record, JSON_UNESCAPED_UNICODE));
        Helper::lotteryRecord($userId, 3);
        jsonReturn(true, 200, '操作成功', ['pid' => $pid, 'prizename' => Constant::LOTTERY_RANK_LIST[$pid]]);
        break;

    /**
     * 获取用户中奖信息
     */
    case 'prize':
        $prizeInfos = $db->select('prize', '*', ['user_id' => $userId]);
        jsonReturn(true, 200, '操作成功', $prizeInfos);
        break;

    case 'info':
        //判断活动状态
        Helper::getStatus($infoStartTime, $infoEndTime, '留资时间');
        $name     = inject_check(getgpc('name'));
        $phone    = inject_check(getgpc('phone'));
        $province = inject_check(getgpc('province'));
        $city     = inject_check(getgpc('city'));
        $address  = inject_check(getgpc('address'));
        if (empty($name) || empty($phone) || empty($province) || empty($city) || empty($address)) {
            jsonReturn(false, 401, '缺少参数');
        }
        //检查手机号格式
        if (!preg_match("/^1[3456789]\d{9}$/", $phone)) {
            jsonReturn(false, 402, '手机号格式错误');
        }
        //检查是否中奖
        $prizeInfo = $db->get('prize', '*', ['user_id' => $userId]);
        if (empty($prizeInfo)) {
            jsonReturn(false, 403, '未中奖');
        }
        $result = $db->update('prize', [
            'name'       => $name,
            'phone'      => $phone,
            'province'   => $province,
            'city'       => $city,
            'address'    => $address,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['user_id' => $userId]);
        if (!$result->rowCount()) {
            jsonReturn(false, 404, '操作失败');
        }
        jsonReturn(true, 200, '操作成功');
        break;
    default:
        header('HTTP / 1.1 404 Not Found');
        header('Status: 404 Not Found');
        exit('404 Not Found');
}


