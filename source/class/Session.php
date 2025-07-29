<?php
namespace Gac163202505;

class Session{
    /**
     * @var string
     */
    private $cacheConfig;

    /**
     * 构造函数
     */
    public function __construct(){
        if(!class_exists("redis", false)){
            die("必须安装redis扩展");
        }
        global $redis_config;
        global $cacheConfig;   //缓存设置
        $this->cacheConfig = $cacheConfig;
        if ($cacheConfig == 'session') {
            $maxLifetime = 86400;
            $host = $redis_config['host'];
            $port = $redis_config['port'];
            $auth = $redis_config['password'];
            if (!empty($auth)) {
                $connect = sprintf("tcp://%s:%u?auth=%s", $host, $port, $auth);
            }else {
                $connect = sprintf("tcp://%s:%u", $host, $port);
            }
        
            ini_set('session.gc_maxlifetime',$maxLifetime);
            ini_set("session.save_handler","redis");
            ini_set("session.save_path", $connect);
            @session_start();
        }
    }

    public function set($key, $value){
        if ($this->cacheConfig == 'session') {
            $_SESSION[$key] = $value;
        }else{
            global $cookiepath;
            global $cookiedomain;
            global $cookieHttpOnly;
            $expire = time() + 86400;
            setcookie($key, $value, $expire, $cookiepath, $cookiedomain, $cookieHttpOnly);
        }
    }

    public function get($key){
        if ($this->cacheConfig == 'session') {
            return $_SESSION[$key];
        }else{
            return $_COOKIE[$key];
        }
    }
}