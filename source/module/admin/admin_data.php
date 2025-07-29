<?php
/*
 *列表管理
 */


use GacBd202411\Constant;
use GacBd202411\Helper;

require_once 'admin_index.php';
$ac = getgpc('ac');


switch ($ac) {
    case 'info':
        $data['project'] = '广本百度地图共创H5';
        $data['time']    = date('Y-m-d', strtotime($startTime)) . '~' . date('Y-m-d', strtotime($infoEndTime));
        $data['desc']    = '请在活动时间范围内操作数据';
        $physicalRate    = $redis->get(Constant::KEY_LOTTERY_RATE[0]);
        $loseRate        = $redis->get(Constant::KEY_LOTTERY_RATE[1]);
        $rankRate        = $redis->get(Constant::KEY_LOTTERY_RANK_RATE[0]);
        $rankLoseRate    = $redis->get(Constant::KEY_LOTTERY_RANK_RATE[1]);
        $total           = ($physicalRate + $loseRate) ?: 100;
        $rankTotal       = ($rankRate + $rankLoseRate) ?: 100;
        //换算成百分比
        $data['physical_rate']  = ($physicalRate / $total) * 100;
        $data['lose_rate']      = ($loseRate / $total) * 100;
        $data['rank_rate']      = ($rankRate / $rankTotal) * 100;
        $data['rank_lose_rate'] = ($rankLoseRate / $rankTotal) * 100;

        echo json_encode(array('result' => true, 'code' => 200, 'message' => '操作成功', 'data' => [$data]));
        break;
    case 'index':
        //参与总人数
        $data['join'] = $db->count('user');
        //今日参与人数
        $data['join_today'] = $db->count('user', ['created_at[<>]' => [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')]]);
        //已发奖品数量
        $data['send_prize'] = $db->count('prize', ['pid' => array_keys(Constant::LOTTERY_LIST)]);
        //已发排行榜奖品数量
        $data['send_rank_prize'] = $db->count('prize', ['pid' => array_keys(Constant::LOTTERY_RANK_LIST)]);

        //剩余奖品数量
        $data['left_prize']      = Constant::LOTTERY_NUM - $data['send_prize'];
        $data['left_rank_prize'] = Constant::LOTTERY_RANK_NUM - $data['send_rank_prize'];

        echo json_encode(array('result' => true, 'code' => 200, 'message' => '操作成功', 'data' => $data));
        break;
    case 'prize_option':
        $data = Constant::LOTTERY_LIST;
        foreach ($data as $key => $value) {
            $data[$key] = ['id' => $key, 'name' => $value];
        }
        $rankData = Constant::LOTTERY_RANK_LIST;
        foreach ($rankData as $key => $value) {
            $rankData[$key] = ['id' => $key, 'name' => $value];
        }
        $data = [
            'physical' => ['id' => 1, 'name' => '普通奖品', 'options' => $data],
            'rank'     => ['id' => 2, 'name' => '排行奖品', 'options' => $rankData],
        ];

        echo json_encode(array('result' => true, 'code' => 200, 'message' => '操作成功', 'data' => $data));
        break;
    case 'prize':
        $page           = getgpc('page') ?: 1;
        $limit          = getgpc('limit') ?: 10;
        $name           = getgpc('name') ?: '';
        $phone          = getgpc('phone') ?: '';
        $pid            = getgpc('pid') ?: '';
        $startTime      = getgpc('startTime') ?: '';
        $endTime        = getgpc('endTime') ?: '';
        $type           = getgpc('type') ?: '';
        $where          = [];
        $where['ORDER'] = ['id' => 'DESC'];
        if (!empty($name)) {
            $where['OR'] = ['nickname[~]' => $name, 'name[~]' => $name];
        }
        if (!empty($phone)) {
            $where['phone[~]'] = $phone;
        }
        if (!empty($pid)) {
            $where['pid'] = $pid;
        }
        if (!empty($type)) {
            $type == 1 ? $where['pid'] = array_keys(Constant::LOTTERY_LIST) : $where['pid[!]'] = array_keys(Constant::LOTTERY_LIST);
        }
        if (!empty($startTime) && !empty($endTime)) {
            $where['created_at[<>]'] = [$startTime . ' 00:00:00', $endTime . ' 23:59:59'];
        }
        if (!empty($startTime) && empty($endTime)) {
            $where['created_at[>=]'] = $startTime . ' 00:00:00';
        }
        if (empty($startTime) && !empty($endTime)) {
            $where['created_at[<=]'] = $endTime . ' 23:59:59';
        }
        $total          = $db->count("prize", "*", $where);
        $where['LIMIT'] = [($page - 1) * $limit, $limit];
        $list           = $db->select("prize", "*", $where);
        foreach ($list as $key => $value) {
            $list[$key]['address'] = $value['province'] . $value['city'] . $value['address'];
            $list[$key]['type']    = in_array($value['pid'], array_keys(Constant::LOTTERY_LIST)) ? 1 : 2;
            //标记IP是否为黑名单
            $list[$key]['is_black'] = $redis->sIsMember(Constant::KEY_IP_BLACKLIST, $value['ip'])?1:0;
        }
        echo json_encode(['result' => true, 'code' => 200, 'data' => $list, 'total' => $total]);
        break;
    case 'export':
        $name           = getgpc('name') ?: '';
        $phone          = getgpc('phone') ?: '';
        $pid            = getgpc('pid') ?: '';
        $startTime      = getgpc('startTime') ?: '';
        $endTime        = getgpc('endTime') ?: '';
        $where          = [];
        $where['ORDER'] = ['id' => 'ASC'];
        if (!empty($name)) {
            $where['OR'] = ['nickname[~]' => $name, 'name[~]' => $name];
        }
        if (!empty($phone)) {
            $where['phone[~]'] = $phone;
        }
        if (!empty($pid)) {
            $where['pid'] = $pid;
        }
        if (!empty($startTime) && !empty($endTime)) {
            $where['created_at[<>]'] = [$startTime . ' 00:00:00', $endTime . ' 23:59:59'];
        }
        if (!empty($startTime) && empty($endTime)) {
            $where['created_at[>=]'] = $startTime . ' 00:00:00';
        }
        if (empty($startTime) && !empty($endTime)) {
            $where['created_at[<=]'] = $endTime . ' 23:59:59';
        }

        $header = ['ID', '昵称', '姓名', '手机号', '奖品ID', '奖品', '类型', '省', '市', '地址', '中奖时间'];
        $data   = [];
        Helper::exportPrizeCSV($header, $where, '中奖记录');
        break;
    case 'set_rate':
        $type = getgpc('type');
        $rate = intval(getgpc('rate'));
        if ($rate < 0) {
            echo json_encode(['result' => false, 'code' => 400, 'message' => '概率不能小于0']);
            exit;
        }
        if ($rate > 100) {
            echo json_encode(['result' => false, 'code' => 400, 'message' => '概率不能大于100']);
            exit;
        }
        $loseRate = 100 - $rate;
        if ($loseRate < 0) {
            echo json_encode(['result' => false, 'code' => 400, 'message' => '概率不能大于100']);
            exit;
        }
        if ($type == 1) {
            //如果是小数,获取小数点后两位,并不要整数位
            $redis->set(Constant::KEY_LOTTERY_RATE[0], $rate);
            $redis->set(Constant::KEY_LOTTERY_RATE[1], $loseRate);
        } else {
            $redis->set(Constant::KEY_LOTTERY_RANK_RATE[0], $rate);
            $redis->set(Constant::KEY_LOTTERY_RANK_RATE[1], $loseRate);
        }
        echo json_encode(['result' => true, 'code' => 200, 'message' => '操作成功']);
        break;
    case 'block':
        $ip = getgpc('ip');
        if (empty($ip)) {
            echo json_encode(['result' => false, 'code' => 400, 'message' => '缺少参数']);
            exit;
        }
        $redis->sAdd(Constant::KEY_IP_BLACKLIST, $ip);
        echo json_encode(['result' => true, 'code' => 200, 'message' => '操作成功']);
        break;

    default:
        echo json_encode(['result' => false, 'code' => 404, 'message' => '接口不存在']);
        break;
}

