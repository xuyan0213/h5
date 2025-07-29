<?php

use GacBd202411\Constant;
use GacBd202411\Helper;

$ac  = getgpc('ac');
$mod = getgpc('mod');
$ip  = getip();
//验签
Helper::checkSign(getgpc());

switch ($ac) {
    case 'user':
        $userInfo = $db->get('user', ['id', 'nickname', 'num', 'appointment', 'game', 'score'], ['id' => $userId]);
        jsonReturn(true, 200, '操作成功', $userInfo);
        break;
    case 'sign':
        //判断活动状态
        Helper::getStatus($startTime, $endTime, '游戏');
        $maxScore = mt_rand(100, 200);
        $uniqueId = Helper::generateUnionId();
        $data     = [
            'user_id'    => $userId,
            'max_score'  => $maxScore,
            'unique_id'  => $uniqueId,
            'start_time' => time(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $db->insert('score_record', $data);
        $value = Helper::encrypt((string)$maxScore);
        jsonReturn(true, 200, '操作成功', ['unique_value' => $value, 'unique_id' => $uniqueId]);
        break;
    case 'appointment':
        $userInfo = $db->get('user', ['id', 'appointment'], ['id' => $userId]);
        if (empty($userInfo)) {
            jsonReturn(false, 401, '请登录');
        }
        //记录到redis中
        if ($userInfo['appointment'] == 0) {
            $redis->sAdd(Constant::KEY_APPOINTMENT, $userId);
            $db->update('user', ['appointment' => 1], ['id' => $userId]);
        }
        jsonReturn(true, 200, '操作成功');
        break;
    case 'score':
        //判断活动状态
        Helper::getStatus($startTime, $endTime, '游戏');
        try {
            $redisScore = 0;
            $result     = $db->action(function ($db) use ($userId, &$redisScore) {
                $score    = getgpc('score');
                $uniqueId = getgpc('unique_id');
                $score = Helper::decrypt($score);
                if (empty($score) || empty($uniqueId)) {
                    jsonReturn(false, 400, '参数错误');
                }
                $scoreInfo = $db->get('score_record', '*', ['unique_id' => $uniqueId, 'user_id' => $userId]);
                $userInfo  = $db->get('user', ['id', 'score', 'game', 'num'], ['id' => $userId]);
                if (empty($scoreInfo)) {
                    jsonReturn(false, 401, '数据异常');
                }
                if ($scoreInfo['max_score'] < $score) {
                    jsonReturn(false, 402, '数据不合法');
                }
                if ($scoreInfo['score'] > 0) {
                    jsonReturn(false, 403, '数据异常');
                }
                $data = [
                    'ip'         => getip(),
                    'score'      => $score,
                    'end_time'   => time(),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $db->update('score_record', $data, ['unique_id' => $scoreInfo['unique_id'], 'user_id' => $scoreInfo['user_id']]);
                $updateData = [];
                if ($userInfo['score'] < $score) {
                    $updateData['score'] = $score;
                    $redisScore          = $score;
                }
                //判断是否第一次玩游戏
                if ($userInfo['game'] == 0 && $userInfo['num'] == 0) {
                    $updateData['game'] = 1;
                    $updateData['num']  = 1;
                } else {
                    $updateData['game[+]'] = 1;
                }
                $db->update('user', $updateData, ['id' => $userId]);
                return true;
            });
            if (!$result) {
                jsonReturn(false, 403, '操作失败');
            }
            if ($redisScore > 0) {
                $redis->zAdd(Constant::KEY_SCORE_RANK, Helper::scoreAddTime($redisScore), $userId);
            }
            $redis->sAdd(Constant::KEY_GAME, $userId);
            jsonReturn(true, 200, '操作成功');
        } catch (Exception $e) {
            jsonReturn(false, 403, '操作失败');
        }
        break;
    case 'rank':
        $rankList = $redis->zRevRange(Constant::KEY_SCORE_RANK, 0, 19, true);
        $data     = $tmp = [];
        $index    = 1;
        foreach ($rankList as $k => $v) {
            $userInfo = $db->get('user', ['nickname'], ['id' => $k]);
            //去掉分数中的小数,并且向下取整
            $tmp[] = [
                'nickname' => $userInfo['nickname'],
                'score'    => floor($v),
                'rank'     => $index,
                'self'     => $k == $userId ? 1 : 0
            ];
            $index++;
        }
        $data['rank'] = $tmp;
        //查询自己的排名和分数
        $userInfo = $db->get('user', ['nickname', 'score'], ['id' => $userId]);
        if (empty($userInfo) || empty($userInfo['score'])) {
            $rank    = 0;
            $score   = 0;
            $is_draw = 0;
        } else {
            $rank    = $redis->zRevRank(Constant::KEY_SCORE_RANK, $userId) + 1;
            $score   = $redis->zScore(Constant::KEY_SCORE_RANK, $userId);
            $is_draw = ($rank <= 20 && time() > strtotime($startRankTime)) ? 1 : 0;
        }

        $data['self'] = [
            'nickname' => $userInfo['nickname'],
            'score'    => floor($score),
            'rank'     => $rank,
            'is_draw'  => $is_draw
        ];
        jsonReturn(true, 200, '操作成功', $data);
        break;
    default:
        header('HTTP/1.1 404 Not Found');
        header('Status: 404 Not Found');
        exit('404 Not Found');
}
