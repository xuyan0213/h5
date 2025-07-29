<?php

// 制定允许其他域名访问

error_reporting(E_ALL);
// ini_set('display_errors', '0');
ini_set('display_errors', '1');

header('Access-Control-Allow-Methods:POST,GET,OPTIONS,DELETE');
header('Access-Control-Allow-Headers:*');
header("Content-type:application/json;charset=utf-8");
header('Access-Control-Allow-Credentials:true');
//注意 有的时候 header("Access-Control-Allow-Origin:*");中不能使用* 所以* 要换成指定的请求接口的域名 这时 我们就可以自动获取前端 或者请求的域名
if (isset($_SERVER['HTTP_ORIGIN']))  //可以加个判断 可不加
    header("Access-Control-Allow-Origin:" . $_SERVER['HTTP_ORIGIN']); //自动获取请求接口的域名


if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    return false;
}
/*
 * 后台管理入口文件
 */
require_once(dirname(__FILE__) . "/data/common.inc.php");
require_once(dirname(__FILE__) . "/data/conn.inc.php");
//连接redis
require_once(dirname(__FILE__) . "/data/conn.redis.php");


$mod = getgpc('mod');
if (!in_array($mod, array('index', 'login','contrast', 'data','activity','phone'))) {
    $mod = 'index';
}

require_once libfile('admin/'.$mod, 'module');