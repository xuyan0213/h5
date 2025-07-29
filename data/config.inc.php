<?php
// 连接数据库参数
$mysqlconfig = [
    'database_type' => 'mysql',
    'database_name' => 'gac_bd_202411_prod',
    'server'        => '127.0.0.01',
    'username'      => 'root',
    'password'      => 'root',
    'charset'       => 'utf8',
    'port'          => 3306,
    'prefix'        => 'gx_',
];

$redis_config = array(
    // redis连接信息
    'host'     => '127.0.0.01',                //服务地址
    'port'     => '6379',                        //端口号
    'password' => '',           //密码
    'area'     => '1'
);

$cacheConfig = 'cookie';   //缓存配置

$filter_input = false;  //开启token验证
$test         = true;           //开启测试信息

$cookiepre      = 'gac_bd_202411_prod:';          // cookie 前缀
$cookiedomain   = ''; // cookie 作用域
$cookiepath     = '/';                         // cookie 作用路径
$cookieHttpOnly = false;                   // cookie 作用路径
$cookieExpired  = 5;                    // cookie 有效期(单位:天)

// 编码
$dbcharset = '';                           // MySQL 字符集, 可选 'gbk', 'big5', 'utf8', 'latin1', 留空为按照网站字符集设定
$charset   = 'UTF-8';                        // 网站页面默认字符集, 可选 'gbk', 'big5', 'utf-8'

//允许访问域名
$allow_origin = [
    'ghac.weiyihui.cn',
];

$env = 'prod'; //版本
$lotteryBlackOpen = true;

//活动时间
$month       = '202411';
$redisPrefix = 'gac_bd_202411_prod:';

$startTime = '2024-11-15 00:00:00';
$endTime   = '2024-11-30 23:59:59';
$startRankTime = '2024-12-01 10:00:00';
$endRankTime   = '2024-12-03 23:59:59';
$infoStartTime = '2024-11-15 00:00:00';
$infoEndTime   = '2024-12-06 23:59:59';

$nowTime    = date('Y-m-d H:i:s');
$today      = date('Y-m-d', strtotime("+0 days"));
$expireTime = mktime(23, 59, 59, date("m"), date("d"), date("Y"));

//测试站
$url = 'http://localhost:8080/gac_bd_202411_prod/index.php';
$domain = 'localhost:8080';