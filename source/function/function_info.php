<?php

use GacLy202409\Constant;




//aes的token验证
function decode_token($str, $params, $field = 'openid')
{
    $aes         = new Aes(substr(md5($str), 0, 16), substr(md5($str), -16));
    $privDecrypt = $aes->decrypt($params);
    $array       = json_decode($privDecrypt, true);

    if (empty($array)) {
        echo json_encode(array('result' => false, 'message' => 'token验证不通过'));
        die;
    }
    if ($array[$field] != $str) {
        echo json_encode(array('result' => false, 'message' => 'token验证不通过'));
        die;
    }
    //判断是否超时
    $time_difference = time() - $array['time'];
    if ($time_difference > 300) {
        echo json_encode(array('result' => false, 'message' => '请求超时'));
        die;
    }
    return true;
}


//是否为微信端
function isWeixin()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return true;
    }
    return false;
}

//生成随机数
function getstr()
{
    $base = 'abcdefghijklmnopqrstuvwsyzABCDEFGHIJKLMNOPQRSTUVWSYZ0123456789';
    $str  = '';
    for ($i = 0; $i < 15; $i++) {
        $str .= substr($base, rand(0, strlen($base) - 1), 1);
    }
    return $str;
}

function getMillisecond()
{
    list($msec, $sec) = explode(' ', microtime());
    $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    return $msectimes = substr($msectime, 0, 13);
}

function http_send($url, $data)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ));
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}


function jsonReturn($returnValue = true, $code = 200, $msg = '', $datalist = null)
{
    $Result['result'] = $returnValue;
    $Result['code']   = $code;
    if ($msg) {
        $Result['msg'] = $msg;
    } else {
        $Result['msg'] = '';
    }
    if ($datalist) {
        $Result['data'] = $datalist;
    } else {
        $Result['data'] = null;
    }
    if (($Result = json_encode($Result, JSON_UNESCAPED_UNICODE)) === false) {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                exit('JSON_ERROR_NONE');
            case JSON_ERROR_DEPTH:
                exit('JSON_ERROR_DEPTH');
            case JSON_ERROR_STATE_MISMATCH:
                exit('JSON_ERROR_STATE_MISMATCH');
            case JSON_ERROR_CTRL_CHAR:
                exit('JSON_ERROR_CTRL_CHAR');
            case JSON_ERROR_SYNTAX:
                exit('JSON_ERROR_SYNTAX');
            case JSON_ERROR_UTF8:
                exit('JSON_ERROR_UTF8');
            case JSON_ERROR_RECURSION:
                exit('JSON_ERROR_RECURSION');
            case JSON_ERROR_INF_OR_NAN:
                exit('JSON_ERROR_INF_OR_NAN');
            case JSON_ERROR_UNSUPPORTED_TYPE:
                exit('JSON_ERROR_UNSUPPORTED_TYPE');
            case JSON_ERROR_INVALID_PROPERTY_NAME:
                exit('JSON_ERROR_INVALID_PROPERTY_NAME');
            case JSON_ERROR_UTF16:
                exit('JSON_ERROR_UTF16');
            default:
                exit('JSON_ERROR_UNKNOWN');
        }
    }
    // 返回JSON数据格式到客户端 包含状态信息
    header('Content-Type:application/json; charset=utf-8');
    exit($Result);
}


if (!function_exists('set_cookie')) {
    function set_cookie($key, $value, $time)
    {
        global $cookiepath;
        global $cookiedomain;
        global $cookieHttpOnly;
        setcookie($key, $value, $time, $cookiepath, $cookiedomain, $cookieHttpOnly);
    }
}


/**
 * 浏览器友好的变量输出
 * @param mixed $var 变量
 * @param boolean $echo 是否输出 默认为true 如果为false 则返回输出字符串
 * @param string $label 标签 默认为空
 * @param integer $flags htmlspecialchars flags
 * @return void|string
 */
function dump($var, $echo = true, $label = null, $flags = ENT_SUBSTITUTE)
{
    $label = (null === $label) ? '' : rtrim($label) . ':';
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
    if (!extension_loaded('xdebug')) {
        $output = htmlspecialchars($output, $flags);
    }
    $output = '<pre>' . $label . $output . '</pre>';
    if ($echo) {
        echo($output);
        return;
    } else {
        return $output;
    }
}


function curl_request($url, $data = null, $method = 'get', $header = array("content-type: application/json"), $json = true, $https = true, $timeout = 60)
{

    global $redis_config;
    global $redisPrefix;
    $redis = new \Redis();
    $redis->connect($redis_config['host'], $redis_config['port']);
    $redis->auth($redis_config['password']);
    $redis->select($redis_config['area']);

    $method = strtoupper($method);
    $ch     = curl_init();                         //初始化
    curl_setopt($ch, CURLOPT_URL, $url);           //访问的URL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//只获取页面内容，但不输出
    if ($https) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//https请求 不验证证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//https请求 不验证HOST
    }
    if ($method != "GET") {
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);//请求方式为post请求
        }
        if ($method == 'PUT' || strtoupper($method) == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); //设置请求方式
        }
        if ($json) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));//请求数据
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));//请求数据
        }

    }
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //模拟的header头

    $obj    = curl_exec($ch);//执行请求
    $result = json_decode($obj, JSON_UNESCAPED_UNICODE);
    //记录请求错误
    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200 || $result['Result'] != 'success') {
        $error['data']     = $data;
        $error['res']      = $result;
        $error['time']     = date('Y-m-d H:i:s');
        $error['httpCode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redis->lPush($redisPrefix . 'api_error', json_encode($error, JSON_UNESCAPED_UNICODE));
        $redis->hSet($redisPrefix . 'api_error_set', $data['timestamp'], json_encode($error, JSON_UNESCAPED_UNICODE));
    }
    curl_close($ch);//关闭curl，释放资源
    return $result;
}





/**
 * 获取日期之间的所有日期
 * @param $startDate
 * @param $endDate
 * @return array
 */
function periodDate($startDate, $endDate)
{
    $startTime = strtotime($startDate);
    $endTime   = strtotime($endDate);
    $arr       = array();
    while ($startTime <= $endTime) {
        $arr[]     = date('Y-m-d', $startTime);
        $startTime = strtotime('+1 day', $startTime);
    }
    return $arr;
}

/**
 * 获取用户真实IP地址
 *
 * @return string 用户的真实IP地址
 */
function getUserIP(): string
{
    $ip = '';

    // 检查 HTTP_CLIENT_IP 头字段
    if (isset($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } // 检查 HTTP_X_FORWARDED_FOR 头字段
    elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        foreach ($ips as $ip) {
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    } // 检查 HTTP_X_FORWARDED 头字段
    elseif (isset($_SERVER['HTTP_X_FORWARDED']) && filter_var($_SERVER['HTTP_X_FORWARDED'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    } // 检查 HTTP_X_CLUSTER_CLIENT_IP 头字段
    elseif (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && filter_var($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    } // 检查 HTTP_FORWARDED_FOR 头字段
    elseif (isset($_SERVER['HTTP_FORWARDED_FOR']) && filter_var($_SERVER['HTTP_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    } // 检查 HTTP_FORWARDED 头字段
    elseif (isset($_SERVER['HTTP_FORWARDED']) && filter_var($_SERVER['HTTP_FORWARDED'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    } // 检查 REMOTE_ADDR 头字段
    elseif (isset($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return $ip;
}


/**
 * 计算今日还剩余多少秒
 * @return int
 */
function getTodaySurplus()
{
    $expireTime = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
    return $expireTime - time();
}


