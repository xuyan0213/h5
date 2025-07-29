<?php
/**
 *        @公用函数库
 *
 */
function encryptName($name)
{
    global $charset;
    $encrypt_name = '';
    //判断是否包含中文字符
    if (preg_match("/[\x{4e00}-\x{9fa5}]+/u", $name)) {
        //按照中文字符计算长度
        $len = mb_strlen($name, $charset);
        if ($len >= 3) {
            //三个字符或三个字符以上掐头取尾，中间用*代替
            $encrypt_name = mb_substr($name, 0, 1, $charset) . str_repeat('*', $len - 2) . mb_substr($name, -1, 1, $charset);
        } elseif ($len == 2) {
            //两个字符
            $encrypt_name = mb_substr($name, 0, 1, $charset) . '*';
        }
    } else {
        //按照英文字串计算长度
        $len = strlen($name);
        if ($len >= 3) {
            //三个字符或三个字符以上掐头取尾，中间用*代替
            $encrypt_name = substr($name, 0, 1) . str_repeat('*', $len - 2) . substr($name, -1);
        } elseif ($len === 2) {
            //两个字符
            $encrypt_name = substr($name, 0, 1) . '*';
        }
    }

    return $encrypt_name;
}

//概率函数
function get_rand($proArr)
{
    $resulta = '';                    //概率数组的总概率精度
    $proSum  = array_sum($proArr);    //概率数组循环
    foreach ($proArr as $key => $proCur) {
        $randNum = mt_rand(1, $proSum);
        if ($randNum <= $proCur) {
            $resulta = $key;
            break;
        } else {
            $proSum -= $proCur;
        }
    }
    unset($proArr);
    return $resulta;
}

function libfile($libname, $folder = '')
{

    $libpath = 'source/' . $folder;
    if (strstr($libname, '/')) {
        list($pre, $name) = explode('/', $libname);
        return realpath("{$libpath}/{$pre}/{$pre}_{$name}.php");
    } else {
        print_r($libpath);
        die();
        return realpath("{$libpath}/{$libname}.php");
    }
}

function libfile_r($libname, $folder = '')
{
    $libpath = '../source/' . $folder;
    if (strstr($libname, '/')) {
        list($pre, $name) = explode('/', $libname);
        return realpath("{$libpath}/{$pre}/{$pre}_{$name}.php");
    } else {
        return realpath("{$libpath}/{$libname}.php");
    }
}


/**
 *    @函数名称：inject_check()
 *    @函数作用：检测提交的值是不是含有SQL注射的字符，防止注射，保护服务器安全
 *    @参　　数：$str: 提交的变量
 *    @返 回 值：返回检测结果，ture or false
 */
function inject_check($str)
{
    $farr = array(
        "/<(\\/?)(script|i?frame|style|html|body|title|link|meta|object|\\?|\\%)([^>]*?)>/isU",
        "/(<[^>]*)on[a-zA-Z]+\s*=([^>]*>)/isU",
        "/select|insert|update|delete|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile|dump/is"
    );
    return preg_replace($farr, '', $str);
}

/**
 *  @超级变量的自定义函数
 *  @参数 $k：变量名称 $type：变量类型如GET、POST，这里默认接收GET和POST的值
 *  @调用方法：getgpc('mod')默认为GET和POST 或者 getgpc("mod",'G')
 */
/*function getgpc($k, $type='GP') 
{
    $type = strtoupper($type);
    switch($type) 
    {
        case 'G': 
            $var = inject_check($_GET); 
        break;
        case 'P': 
            $var = inject_check($_POST); 
        break;
        case 'C': 
            $var = inject_check($_COOKIE); 
        break;
        default:
            if(isset($_GET[$k])) 
            {
                $var = inject_check($_GET);
            }
            else
            {
                $var = inject_check($_POST);
            }
        break;
    }
    return isset($var[$k]) ? $var[$k] : NULL;
}*/

function getgpc($k = '', $type = 'GP')
{
    $type = strtoupper($type);
    switch ($type) {
        case 'G':
            $var = &$_GET;
            break;
        case 'P':
            $var = &$_POST;
            break;
        case 'C':
            $var = &$_COOKIE;
            break;
        default:
            if (isset($_GET[$k])) {
                $var = &$_GET;
            } else {
                $var = &$_POST;
            }
            break;
    }
    if (empty($k)) {
        return $var;
    }

    return isset($var[$k]) ? $var[$k] : NULL;
}


/**
 *   @获取IP
 */
function GetIP()
{
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        $cip = $_SERVER["HTTP_CLIENT_IP"];
    } else if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    } else if (!empty($_SERVER["REMOTE_ADDR"])) {
        $cip = $_SERVER["REMOTE_ADDR"];
    } else {
        $cip = '';
    }
    preg_match("/[\d\.]{7,15}/", $cip, $cips);
    $cip = isset($cips[0]) ? $cips[0] : 'unknown';
    unset($cips);
    unset($cips);
    return $cip;
}


/**
 *  获取验证码的session值
 *
 * @return    string
 */
function GetCkVdValue()
{
    @session_id($_COOKIE['PHPSESSID']);
    @session_start();
    return isset($_SESSION['securimage_code_value']) ? $_SESSION['securimage_code_value'] : '';
}


/**
 *  PHP某些版本有Bug，不能在同一作用域中同时读session并改注销它，因此调用后需执行本函数
 *
 * @return    void
 */
function ResetVdValue()
{
    @session_start();
    $_SESSION['securimage_code_value'] = '';
}


/**
 * 动态翻页控制设置
 * @   $mode 控制样式，0-简单型 1-完整型
 * @   $file 当前页文件名
 * @   $record_count 总计
 * @   $page_count 每页条数
 * @   $page_current 当前页
 */
function nextpage($record_count, $page_current, $file = "", $page_count = 10, $mode = 1)
{
    $page = "";
    if ($record_count > $page_count) {
        $page_size = ($record_count % $page_count) > 0 ? (int)($record_count / $page_count) + 1 : $record_count / $page_count;
    } else {
        $page_size = 1;
    }
    if ($mode) {
        $page .= "    
        <div class=\"pull-left\">
            <div class=\"form-group form-inline\">
                总共{$page_size}页， 当前第{$page_current}页，共{$record_count}条数据。
            </div>
        </div>";
    }

    if ($page_current < 1 || $page_current > $page_size) {
        $page_current = 1;
    }
    $up   = $page_current == 1 ? $page_current : $page_current - 1;
    $down = $page_current == $page_size ? $page_current : $page_current + 1;

    if ($page_size > 3 && $page_current > 2 && $page_current < $page_size) {
        $sizelist = "<li><a href=\"$file=$up\">$up</a></li><li><a href=\"$file=$page_current\">$page_current</a></li><li><a href=\"$file=$down\">$down</a></li>";
    } else {
        $sizelist = '';
    }

    $page .= "<div class=\"box-tools pull-right\">
            <ul class=\"pagination\"><li>
                    <a href=\"$file=1\" aria-label=\"Previous\">首页</a>
                </li>
                <li><a href=\"$file=$up\">上一页</a></li>
                $sizelist
                <li><a href=\"$file=$down\">下一页</a></li>
                <li>
                    <a href=\"$file=$page_size\" aria-label=\"Next\">尾页</a>
                </li>
            </ul>
        </div>";

    return $page;
}

/**
 *  @提示信息
 *
 */

function showmessage($msg, $gourl, $onlymsg = 0, $limittime = 0)
{
    if (empty($GLOBALS['cfg_plus_dir'])) $GLOBALS['cfg_plus_dir'] = '..';

    $htmlhead = "<html>\r\n<head>\r\n<title>友情提示</title>\r\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\r\n";
    $htmlhead .= "<base target='_self'/>\r\n<style>div{line-height:160%;}</style></head>\r\n<body leftmargin='0' topmargin='0' bgcolor='#FFFFFF'>" . (isset($GLOBALS['ucsynlogin']) ? $GLOBALS['ucsynlogin'] : '') . "\r\n<center>\r\n<script>\r\n";
    $htmlfoot = "</script>\r\n</center>\r\n</body>\r\n</html>\r\n";

    $litime = ($limittime == 0 ? 1000 : $limittime);
    $func   = '';

    if ($gourl == '-1') {
        if ($limittime == 0) $litime = 5000;
        $gourl = "javascript:history.go(-1);";
    }

    if ($gourl == '' || $onlymsg == 1) {
        $msg = "<script>alert(\"" . str_replace("\"", "“", $msg) . "\");</script>";
    } else {
        //当网址为:close::objname 时, 关闭父框架的id=objname元素
        if (preg_match('/close::/', $gourl)) {
            $tgobj = trim(preg_replace('/close::/', '', $gourl));
            $gourl = 'javascript:;';
            $func  .= "window.parent.document.getElementById('{$tgobj}').style.display='none';\r\n";
        }

        $func .= "var pgo=0;
                  function JumpUrl(){
                    if(pgo==0){ location='$gourl'; pgo=1; }
                  }\r\n";
        $rmsg = $func;
        $rmsg .= "document.write(\"<br /><div style='width:450px;padding:0px;border:1px solid #DADADA;'>";
        $rmsg .= "<div style='padding:6px;font-size:12px;border-bottom:1px solid #DADADA;background:#DBEEBD url({$GLOBALS['cfg_plus_dir']}/img/wbg.gif)';'><strong>友情提示！</strong></div>\");\r\n";
        $rmsg .= "document.write(\"<div style='height:130px;font-size:10pt;background:#ffffff'><br />\");\r\n";
        $rmsg .= "document.write(\"" . str_replace("\"", "“", $msg) . "\");\r\n";
        $rmsg .= "document.write(\"";

        if ($onlymsg == 0) {
            if ($gourl != 'javascript:;' && $gourl != '') {
                $rmsg .= "<br /><a href='{$gourl}'>如果你的浏览器没反应，请点击这里...</a>";
                $rmsg .= "<br/></div>\");\r\n";
                $rmsg .= "setTimeout('JumpUrl()',$litime);";
            } else {
                $rmsg .= "<br/></div>\");\r\n";
            }
        } else {
            $rmsg .= "<br/><br/></div>\");\r\n";
        }
        $msg = $htmlhead . $rmsg . $htmlfoot;
    }
    echo $msg;
}

/**
 *  设置cookie用的
 * @para string $var cookie名
 * @para string $value cookie值
 * @para int $life 生存时间
 * @para int $prefix cookie前缀
 *
 */

function dsetcookie($var, $value, $life = 0, $prefix = 1)
{
    global $cookiepre, $admincookiedomain, $cookiepath, $timestamp, $_SERVER;
    //echo $prefix."--".$var."--".$value."--".$life."--".$cookiepath;

    setcookie(($prefix ? $cookiepre : '') . $var, $value,
        $life ? time() + $life : 0, $cookiepath,
        $admincookiedomain, $_SERVER['SERVER_PORT'] == 443 ? 1 : 0);
}

/**
 * 字符串加密以及解密函数
 *
 * @param string $string 原文或者密文
 * @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
 * @param string $key 密钥
 * @param int $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效
 * @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
 *
 * @example
 *
 *     $a = authcode('abc', 'ENCODE', 'key');
 *     $b = authcode($a, 'DECODE', 'key'); // $b(abc)
 *
 *     $a = authcode('abc', 'ENCODE', 'key', 3600);
 *     $b = authcode('abc', 'DECODE', 'key'); // 在一个小时内，$b(abc)，否则 $b 为空
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
{
    $ckey_length = 4;
    $key         = md5($key != '' ? $key : 'insunh5');
    $keya        = md5(substr($key, 0, 16));
    $keyb        = md5(substr($key, 16, 16));
    $keyc        = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

    $cryptkey   = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);

    $string        = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);

    $result = '';
    $box    = range(0, 255);

    $rndkey = array();
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; $i++) {
        $j       = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp     = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a       = ($a + 1) % 256;
        $j       = ($j + $box[$a]) % 256;
        $tmp     = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result  .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if ($operation == 'DECODE') {
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc . str_replace('=', '', base64_encode($result));
    }
}