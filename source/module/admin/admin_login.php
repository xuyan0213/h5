<?php
$ac = getgpc("ac");

use GacBd202411\Captcha;
use GacBd202411\Helper;

switch ($ac) {
    //生成验证码
    case "captcha":

        $captcha      = new Captcha();
        $captchaArray = $captcha->render();
        $redis->setex($redisPrefix . "captcha_" . $captchaArray['captcha_id'], 600, $captchaArray['captcha']);
        echo json_encode(['result' => true, 'code' => 200, 'img' => $captcha->show(), 'captcha_id' => $captchaArray['captcha_id']]);
        break;

    case "login":
        $data       = json_decode(file_get_contents('php://input'), true);
        $code       = strtoupper($data["captcha"]);
        $captcha_id = $data["captcha_id"];
        $username   = inject_check($data['username']);
        $password   = inject_check($data['password']);
        $pwd        = md5($password);

        require_once dirname(__FILE__) . "/../../../data/conn.redis.php";
        $svali = $redis->get($redisPrefix . "captcha_" . $captcha_id);
        //判断登录
        if (empty($username) || empty($password)) {
            echo json_encode(['result' => false, 'code' => 500001, 'message' => '请输入用户名或密码']);
            break;
        }
        //判断验证码
        if ($code == '' || $code != $svali) {
            echo json_encode(['result' => false, 'code' => 500002, 'message' => '验证码错误']);
            break;
        }
        require_once dirname(__FILE__) . "/../../../data/conn.inc.php";
        $userlist = $db->select("admin", [
            "username",
            "password",
            "aid",
            "code",
            "realname",
            "typeid",
            "typename",
        ], [
            "username" => $username
        ]);

        if (isset($userlist[0])) {
            $user = $userlist[0];
        } else {
            echo json_encode(['result' => false, 'code' => 500003, 'message' => '用户名或密码错误']);
            break;
        }

        if (!isset($user["username"])) {
            echo json_encode(['result' => false, 'code' => 500003, 'message' => '用户名或密码错误']);
            break;
        } else if ($pwd != $user["password"]) {
            echo json_encode(['result' => false, 'code' => 500003, 'message' => '用户名或密码错误']);
            break;
        }
        //登陆成功,生成token
        $userInfo = [
            'aid'      => $user['aid'],
            'username' => $user['username'],
            'code'     => $user['code'],
            'realname' => $user['realname'],
            'avatar'   => 'https://wpimg.wallstcn.com/f778738c-e4f8-4870-b634-56703b4acafe.gif'
        ];
        $token    = Helper::getToken($userInfo);
        //更新登录时间

        $db->update("admin", [
            "logintime" => date('Y-m-d H:i:s', time()),
            "loginip"   => getUserIP(),
            "code"      => $token,
        ], [
            "aid" => $user['aid']
        ]);

        echo Helper::respondWithToken($token);
        break;
    case  'verify':
        $token  = getgpc("token");
        $result = Helper::verifyToken($token);
        if ($result) {
            echo json_encode(['result' => true, 'code' => 200, 'message' => '验证成功', 'data' => $result]);
        } else {
            echo json_encode(['result' => false, 'code' => 500010, 'message' => '验证失败']);
        }
        break;

    case "loginout":
        dsetcookie("access_token", '', -86400 * 365);
        echo json_encode(['result' => true, 'code' => 200, 'message' => '退出成功']);
        break;

    default:
        echo json_encode(['result' => false, 'code' => 404, 'message' => '接口不存在']);
        break;
}