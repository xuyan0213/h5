<?php

use GacBd202411\Helper;
use GacBd202411\RateLimiter;
use GacBd202411\Jwt;

/*
 * 首页入口文件
*/
require_once(dirname(__FILE__) . "/data/common.inc.php");

//禁止iframe页面嵌套
header("X-Frame-Options: DENY");

header('Access-Control-Allow-Headers:X-Requested-isinsunh5');

//error_reporting(E_ALL);
ini_set('display_errors', '0');
//ini_set('display_errors', '1');

$mod = getgpc('mod');
if (!in_array($mod, array('index', 'info', 'login', 'houtai', 'lottery'))) {
    $mod = 'index';
}
if (in_array($mod, array('info', 'login', 'lottery'))) {
    $ip          = getUserIP();
    $rateLimiter = new RateLimiter($redis, $redisPrefix, 3600, 60);
    $res         = $rateLimiter->allowRequest($ip);
    if (!$res) {
        jsonReturn(false, 429, '访问频繁');
    }
}
$userId = 0;
//验证是否登录
if (!in_array($mod, array('login', 'houtai'))) {
    $token = $_COOKIE['token'];
    if (empty($token)) {
        jsonReturn(false, 401, '请登录');
    }
    $jwt = new Jwt();
    $res = $jwt->verifyToken($token);

    if (!$res) {
        jsonReturn(false, 402, '请登录');
    }
    $userId   = $res['userId'];
    $nickname = $res['nickname'];
    $unionid  = $res['unionid'];
    $isUser   = $db->has('user', ['unionid' => $unionid]);
    if (!$isUser) {
        jsonReturn(false, 403, '请登录');
    }
}

Helper::apiLog($userId, $db);
require_once libfile('index/' . $mod, 'module');

