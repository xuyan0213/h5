<?php

use GacBd202411\Constant;

require_once 'admin_index.php';

$ac = getgpc('ac');


switch ($ac) {

    case 'index':
        $date      = getgpc('date');
        $days      = periodDate($startTime, $endTime);
        $datas     = $db->select("setlist", [
            "id",
            "pid",
            "pname",
            "num",
            "pdate",
            "redisname",
        ], [
            'pid'   => array_keys(Constant::LOTTERY_LIST),
            "ORDER" => ["id" => "ASC"],
        ]);
        $listname  = [];
        $options[] = ['label' => '请选择日期', 'value' => ''];
        foreach ($days as $key => $value) {
            $listname  += [
                $value => $value . '日奖品',
            ];
            $options[] = [
                'label' => $value, 'value' => $value
            ];
        }
        $nowList = [];
        $allList = [];
        foreach ($datas as $data) {
            //echo "pid:" . $data["pid"] . " - pname:" . $data["pname"] . "";
            $pid       = $data['pid'];
            $listid    = $data['pdate'];
            $redisname = $data['redisname'];
            $pname     = $data['pname'];
            $num       = $data['num'];
            //计算奖品剩余数量
            $mnumlist = $listname[$listid];
            //MYSQL中奖入库数据
            $mnum = $db->count("prize", [
                "pid"  => $pid,
                'date' => $listid
            ]);

            //获取redis奖池队列数据

            $list = $redis->lRange($redisname, 0, -1);
            //统计数组中数字出现的次数
            $redislist = array_count_values($list);

            $rnum = null;
            $rnum = $redislist[$pid] ?? 0;
            if (empty($rnum)) {
                $rnum = 0;
            }

            //MYSQL数量和Redis数量对比颜色区分显示
            if ($num == $rnum) {
                $td = null;
                $th = "class=\"spec\"";
            } else {
                if ($num == $rnum + $mnum) {
                    $td = "class=\"alt\"";
                    $th = "class=\"specalt\"";
                } else {
                    $td = "class=\"alt\"";
                    $th = "class=\"red\"";
                }
            }
            if ($date == $listid) {
                $nowList[] = [
                    'mnumlist'  => $mnumlist,
                    'redisname' => $redisname,
                    'pid'       => $pid,
                    'pname'     => $pname,
                    'num'       => $num,
                    'mnum'      => $mnum,
                    'rnum'      => $rnum,
                    'same'      => $num == $rnum + $mnum,
                ];
            }
            $allList[] = [
                'mnumlist'  => $mnumlist,
                'redisname' => $redisname,
                'pid'       => $pid,
                'pname'     => $pname,
                'num'       => $num,
                'mnum'      => $mnum,
                'rnum'      => $rnum,
                'same'      => $num == $rnum + $mnum,
            ];

        }
        $data = [
            'allList' => $allList,
            'nowList' => $nowList,
        ];

        echo json_encode(['result' => true, 'code' => 200, 'data' => $data, 'options' => $options]);
        break;

    case 'rank':
        $datas    = $db->select("setlist", [
            "id",
            "pid",
            "pname",
            "num",
            "pdate",
            "redisname",
        ], [
            'pid'   => array_keys(Constant::LOTTERY_RANK_LIST),
            "ORDER" => ["id" => "ASC"],
        ]);
        $list     = [];
        $rnumList = $redis->lRange(Constant::KEY_LOTTERY_RANK_LIST, 0, -1);
        $rnumList = array_count_values($rnumList);
        foreach ($datas as $data) {
            $pid       = $data['pid'];
            $redisname = $data['redisname'];
            $pname     = $data['pname'];
            $num       = $data['num'];

            //MYSQL中奖入库数据
            $mnum = $db->count("prize", [
                "pid" => $pid,
            ]);
            $rnum = $rnumList[$pid] ?? 0;

            if ($num == $rnum) {
                $td = null;
                $th = "class=\"spec\"";
            } else {
                $td = "class=\"alt\"";
                if ($num == $rnum + $mnum) {
                    $th = "class=\"specalt\"";
                } else {
                    $th = "class=\"red\"";
                }
            }
            $list[] = [
                'redisname' => $redisname,
                'pid'       => $pid,
                'pname'     => $pname,
                'num'       => $num,
                'mnum'      => $mnum,
                'rnum'      => $rnum,
                'same'      => $num == $rnum + $mnum,
            ];

        }
        echo json_encode(['result' => true, 'code' => 200, 'data' => $list]);
        break;
    default:
        echo json_encode(['result' => false, 'code' => 404, 'message' => '接口不存在']);
        break;
}