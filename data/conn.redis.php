<?php
/*
 *   初始化 redis数据连接 
*/
$redis = new \Redis();
$redis->connect($redis_config['host'],$redis_config['port']);
$redis->auth($redis_config['password']);
$redis->select($redis_config['area']);