<?php
//判断登录状态
use GacBd202411\Helper;

if (empty($_SERVER['HTTP_X_TOKEN'])) {
    echo json_encode(['result' => false, 'code' => 401, 'message' => 'token失效,请重新登录']);
    exit;
}
$token = $_SERVER['HTTP_X_TOKEN'];
$user  = Helper::verifyToken($token);
if (!$user) {
    echo json_encode(['result' => false, 'code' => 401, 'message' => 'token失效,请重新登录']);
    exit;
}
