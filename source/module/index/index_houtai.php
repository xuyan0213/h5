<?php
header('HTTP/1.1 404 Not Found');
header('Status: 404 Not Found');
exit('404 Not Found');
use GacBd202411\Constant;
use GacBd202411\Sign;

ini_set("display_errors", "On");
$ac = getgpc('ac');

switch ($ac) {

    case  "clear":
        $keys = $redis->keys(Constant::REDIS_PREFIX . '*');
        $redis->del($keys);
        foreach (Constant::CLEAR_MYSQL_LIST as $v) {
            $db->query("TRUNCATE " . $v);
        }
        echo '数据清除成功';
        echo "<a href='index.php?mod=houtai'>返回继续操作</a>";
        break;

    case 'set_prize':
        $isSet = $redis->keys(Constant::KEY_LOTTERY_PHYSICAL_LIST . '*');
        if (!empty($isSet)) {
            die("奖品已设置 <br/> <a href='index.php?mod=houtai'>返回继续操作</a>");
        }
        setPrize($startTime, $endTime);

        echo '设置成功';
        echo "<a href='index.php?mod=houtai'>返回继续操作</a>";
        break;

    case 'sign':
        $data   = json_decode(file_get_contents('php://input'), true);
        $sign   = new Sign();
        $string = $sign->setSign($data);
        echo json_encode(['sign' => $string, 'data' => $data]);
        break;


    default:
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        h1 {
            color: #333;
        }
        .links a {
            display: inline-block;
            margin: 10px 10px 10px 0;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        .links a:hover {
            background-color: #2366b1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: #fff;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #ddd;
        }
    </style>
</head>
<body>
    <h1>后台管理</h1>
    <div class="links">
        <a href="index.php?mod=houtai&ac=clear">清空数据库</a>
        <a href="index.php?mod=houtai&ac=set_prize">设置奖品</a>
    </div>';

        $dayList    = periodDate($startTime, $endTime);
        $prizeList  = $redis->keys(Constant::KEY_LOTTERY_PHYSICAL_LIST . '*');
        $prizeList1 = $redis->keys(Constant::KEY_LOTTERY_RANK_LIST);

        echo '<h1>奖品数量</h1>
    <table>
        <thead>
            <tr>
                <th>奖品</th>
                <th>剩余奖品总数</th>
            </tr>
        </thead>
        <tbody>';
        $prizeList1 = $redis->lRange(Constant::KEY_LOTTERY_RANK_LIST, 0, -1);
        //按值排序 越小越靠前
        sort($prizeList1);
        $prizeListArr = array_count_values($prizeList1);
        foreach ($prizeListArr as $prizeId => $count) {
            echo '<tr>';
            echo '<td>' . Constant::LOTTERY_RANK_LIST[$prizeId] . '</td>';
            echo '<td>' . $count . '</td>';
            echo '</tr>';
        }
        echo '<tr>';
        echo '<td>总计</td>';
        echo '<td style="color: #1aad19;font-size: large">' . count($prizeList1) . '</td>';
        echo '</tr>';

        echo '        </tbody>
    </table>
    <table>
        <thead>
            <tr>
                <th>日期</th>
                <th>剩余奖品总数</th>
                <th>奖品详情</th>
            </tr>
        </thead>
        <tbody>';
        foreach ($dayList as $k => $v) {
            $prizeNum = $redis->lLen(Constant::KEY_LOTTERY_PHYSICAL_LIST . $v);
            echo '<tr>';
            echo '<td>' . $dayList[$k] . '</td>';
            echo '<td>' . $prizeNum . '</td>';
            echo '<td>';
            $prizeArr = $redis->lRange(Constant::KEY_LOTTERY_PHYSICAL_LIST . $v, 0, -1);

            $prizeArr = array_count_values($prizeArr);
            //排序
            ksort($prizeArr);
            foreach ($prizeArr as $prizeId => $count) {
                echo Constant::LOTTERY_LIST[$prizeId] . ' 剩余数量：' . $count . '<br>';
            }
            echo '</td>';
            echo '</tr>';
        }

        echo '        </tbody>
    </table>
</body>
</html>';
        break;
}


function setPrize($startTime, $endTime)
{
    global $redis;
    global $db;
    ini_set("memory_limit", "1024M");
    //放入数据库
    $dayList   = periodDate($startTime, $endTime);
    $file      = fopen('/usr/app/gac_bd_202411_prod/uploads/普通奖品设置.csv', 'r');
    $prizeList = [];
    while ($data = fgetcsv($file)) {
        $prizeList[] = $data;
    }
    fclose($file);
    array_shift($prizeList);
    //排行榜奖品
    $file1         = fopen('/usr/app/gac_bd_202411_prod/uploads/排行榜奖品设置.csv', 'r');
    $rankPrizeList = [];
    while ($data = fgetcsv($file1)) {
        $rankPrizeList[] = $data;
    }
    fclose($file1);
    /**
     * [[1=>2,2=>3],[1=>0,2=>1],[1=>1,2=>0]]修改为[[1=>2,1=>0,1=>1],[2=>3,2=>1,2=>0]]
     */
    //重组数据,每列为一天的奖品数量,并且去掉空值,并且去掉第一列
    $prizeList = array_map(null, ...$prizeList);
    array_shift($prizeList);
    //根据每天奖品设置奖品队列
    foreach ($dayList as $k => $v) {
        $currentPrizeList = $prizeList[$k];
        //根据奖品数量设置奖品队列
        $physicalPrizes = [];
        $pipe           = $redis->multi(Redis::PIPELINE);
        foreach ($currentPrizeList as $key => $value) {
            $prizeId  = $key + 1;
            $prizeNum = $value;
            if ($prizeNum) {
                $physicalPrizes = array_merge($physicalPrizes, array_fill(0, $prizeNum, $prizeId));

                //保存到mysql
                $data = [
                    'listid'     => 1,
                    'pid'        => $prizeId,
                    'pname'      => Constant::LOTTERY_LIST[$prizeId],
                    'num'        => $prizeNum,
                    'redisname'  => Constant::KEY_LOTTERY_PHYSICAL_LIST . $v,
                    'pdate'      => $v,
                    'price'      => 0,
                    'type'       => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $db->insert('setlist', $data);
            }
            shuffle($physicalPrizes);

        }
        //$physicalPrizes二维数组转一维数组
        if (!empty($physicalPrizes)) {
            $pipe->lPush(Constant::KEY_LOTTERY_PHYSICAL_LIST . $v, ...$physicalPrizes);
        }
        $pipe->exec();
    }
    //排行榜奖品
    $rankPrizes = [];
    $pipe       = $redis->multi(Redis::PIPELINE);
    foreach ($rankPrizeList as $key => $value) {
        $prizeId  = $key + array_keys(Constant::LOTTERY_LIST)[count(Constant::LOTTERY_LIST) - 1] + 1;
        $prizeNum = $value[1];
        if ($prizeNum) {
            $rankPrizes = array_merge($rankPrizes, array_fill(0, $prizeNum, $prizeId));
            //保存到mysql
            $data = [
                'listid'     => 2,
                'pid'        => $prizeId,
                'pname'      => Constant::LOTTERY_RANK_LIST[$prizeId],
                'num'        => $prizeNum,
                'redisname'  => Constant::KEY_LOTTERY_RANK_LIST,
                'pdate'      => '',
                'price'      => 0,
                'type'       => 2,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $db->insert('setlist', $data);
        }
        shuffle($rankPrizes);
    }
    if (!empty($rankPrizes)) {
        $pipe->lPush(Constant::KEY_LOTTERY_RANK_LIST, ...$rankPrizes);
    }
    $pipe->exec();
    //设置默认中奖概率
    $redis->set(Constant::KEY_LOTTERY_RATE[0], 1);
    $redis->set(Constant::KEY_LOTTERY_RATE[1], 99);
    $redis->set(Constant::KEY_LOTTERY_RANK_RATE[0], 1);
    $redis->set(Constant::KEY_LOTTERY_RANK_RATE[1], 99);

}
