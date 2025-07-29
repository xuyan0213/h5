<?php

use GacBd202411\Jwt;
use GacBd202411\Helper;
use GacBd202411\Constant;

$ac  = getgpc('ac');
$mod = getgpc('mod');
$ip  = getUserIP();

Helper::checkSign(getgpc());
//表单验证
$nickname = trim(getgpc('nickname'));
if (empty($nickname)) {
    jsonReturn(false, 401, '请输入昵称');
}
//检查昵称是否存在
if ($db->has('user', ['nickname' => $nickname])) {
    jsonReturn(false, 402, '昵称已存在');
}
//参数过滤,避免sql注入
$nickname = inject_check($nickname);
$unionid  = Helper::generateUnionId();
$data     = [
    'unionid'    => $unionid,
    'nickname'   => $nickname,
    'ip'         => $ip,
    'token'      => '',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];
$db->insert('user', $data);
$userId = $db->id();
if (!$userId) {
    jsonReturn(false, 403, '登录失败');
}
$redis->set(Constant::KEY_MAX_USER_ID, $userId);
$data = [
    'user_id'  => $userId,
    'ip'       => $ip,
    'nickname' => $nickname,
];
//使用
$jwt   = new Jwt();
$data  = [
    'iss'      => 'jwt_admin',
    'iat'      => time(),
    'exp'      => time() + 30 * 24 * 3600,
    'nbf'      => time(),
    'sub'      => $_SERVER['HTTP_HOST'],//TODO:这里可以换成你的域名
    'jti'      => md5(uniqid('JWT') . time()),
    'nickname' => $nickname,
    'ip'       => $ip,
    'userId'   => $userId,
    'unionid'  => $unionid,
];
$token = $jwt->getToken($data);
$db->update('user', ['token' => $token]);
//设置token
setcookie('token', $token, time() + 3600 * 24 * 30, '/');
jsonReturn(true, 200, '操作成功');
