<?php
/*
 *列表管理
 */
//判断登录状态
if (empty($_SERVER)) {
    echo json_encode(['result' => false, 'code' => 401, 'message' => '请登录']);
    exit;
}
$token = $_SERVER['HTTP_X_TOKEN'];
$user  = verifyToken($token);
if (!$user) {
    echo json_encode(['result' => false, 'code' => 401, 'message' => 'token失效,请重新登录']);
    exit;
}

$ac = isset($_REQUEST["ac"]) ? $_REQUEST["ac"] : '';


switch ($ac) {
    case 'list':
        $data = json_decode(file_get_contents('php://input'), true);
        $page    = $data['page'] ?: 1;
        $limit   = $data['limit'] ?: 10;
        $keyword = $data['name'] ?: 0;
        $status  = $data['status']?: '';

        $where   = [];
        // WHERE `status` = 1 AND  FIND_IN_SET(2, `cate_id`) ORDER BY `id` ASC
        if (!empty($keyword)) {
            $where['OR']['name[~]'] = $keyword;
        }
        if ($status !== '') {
            $where['status'] = $status;
        }
        $where['ORDER'] = ['id' => 'DESC',];
        $total          = $db->count("activity", "*", $where);
        $where['LIMIT'] = [($page - 1) * $limit, $limit];
        $list           = $db->select("activity", '*', $where);
        //获取当前域名
        $domain = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        foreach ($list as &$item) {
            $item['url'] = $domain . '/lantu_live_draw/index.php?id=' . $item['id'];
            $item['draw'] = $redis->get($redisPrefix . 'lucky_mobile:' . $item['id'])?:'';
        }
        echo json_encode(['result' => true, 'code' => 200, 'total' => $total, 'data' => $list]);
        break;
    case 'add':
        $data = json_decode(file_get_contents('php://input'), true);
        $name    = $data['name'];
        $date         = $data['date'] ?: date('Y-m-d');
        if (empty($name)) {
            echo json_encode(['result' => false, 'code' => 101, 'message' => '缺少参数']);
            break;
        }
        $db->insert('activity', [
            'name'       => $name,
            'date'       => $date,
            'status'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        echo json_encode(['result' => true, 'code' => 200, 'message' => '新增成功']);
        break;
    case 'update':
        $id     = getgpc('id');
        $status = getgpc('status');
        if (empty($id)) {
            echo json_encode(['result' => false, 'code' => 101, 'message' => '缺少参数']);
            break;
        }
        $activityInfo = $db->update('activity', ['status' => 1], ['id' => $id]);
        //获取已中奖用户并放入重置KEY中,留存记录
        $luckyMobile = $redis->get($redisPrefix . 'lucky_mobile:' . $id);
        $redis->sAdd($redisPrefix . 'reset_mobile_list:' . $id, $luckyMobile);
        //删除已中奖用户
        $redis->del($redisPrefix . 'lucky_mobile:' . $id);
        //删除中奖队列中的数据
        $redis->sRem($redisPrefix . 'pop_mobile:' . $id, $luckyMobile);
        echo json_encode(['result' => true, 'code' => 200, 'message' => '重置成功']);
        break;
    default:
        echo json_encode(['result' => false, 'code' => 404, 'message' => '接口不存在']);
        break;

}

function getBetweenTime($start, $end)
{
    $response = [];

    $dt_start = strtotime($start);
    $dt_end   = strtotime($end);
    while ($dt_start <= $dt_end) {
        $response[] = date('Y-m-d', $dt_start);
        $dt_start   = strtotime('+1 day', $dt_start);
    }
    return $response;
}

function excelTime($date, $time = false)
{
    if (function_exists('GregorianToJD')) {
        if (is_numeric($date)) {
            $jd        = GregorianToJD(1, 1, 1970);
            $gregorian = JDToGregorian($jd + intval($date) - 25569);
            $date      = explode('/', $gregorian);
            $date_str  = str_pad($date [2], 4, '0', STR_PAD_LEFT)
                . "-" . str_pad($date [0], 2, '0', STR_PAD_LEFT)
                . "-" . str_pad($date [1], 2, '0', STR_PAD_LEFT)
                . ($time ? " 00:00:00" : '');
            return $date_str;
        }
    } else {
        $date = $date > 25568 ? $date + 1 : 25569;
        /*There was a bug if Converting date before 1-1-1970 (tstamp 0)*/
        $ofs  = (70 * 365 + 17 + 2) * 86400;
        $date = date("Y-m-d", ($date * 86400) - $ofs) . ($time ? " 00:00:00" : '');
    }
    return $date;
}

