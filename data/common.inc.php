<?php
/**
 *  @公共文件
 *
 */

//数据库配置
require_once(dirname(__FILE__) . "/db_mysql.class.php");
require_once(dirname(__FILE__) . "/config.inc.php");
require_once(dirname(__FILE__) . "/conn.inc.php");
require_once(dirname(__FILE__) . "/conn.redis.php");

//公用函数
require_once(dirname(__FILE__) . "/../source/function/function_core.php");
require_once(dirname(__FILE__) . "/../source/function/function_info.php");
require_once(dirname(__FILE__) . "/../source/class/Constant.php");
require_once(dirname(__FILE__) . "/../source/class/Captcha.php");
require_once(dirname(__FILE__) . "/../source/class/Aes.php");
require_once(dirname(__FILE__) . "/../source/class/Session.php");
require_once(dirname(__FILE__) . "/../source/class/Jwt.php");
require_once(dirname(__FILE__) . "/../source/class/Sign.php");
require_once(dirname(__FILE__) . "/../source/class/RateLimiter.php");
require_once(dirname(__FILE__) . "/../source/class/Helper.php");
