<?php
//判断登录
if(!isset($_COOKIE['auth'])){
	//没有auth，提示登录
	//1401;
	header("Location:$admin_login");
	exit;
}
$key='insunh5';
$auth = explode("\t", authcode($_COOKIE['auth'], 'DECODE',$key));
list($uid,$username,$admincode,$vcode,$nowtime) = $auth;
if($uid)
{
	//设置登录有效时间
	$systemtime = time();
	$admintime = $systemtime - $nowtime;
	if($admintime>3600)
	{
		//登录时效过期，提示登录
		//1402;
		header("Location:$admin_login");
		exit;
	}
	
	//获取auth值
	$a1 = array($uid,$username,$admincode,$vcode);
	//获取COOKIE
	$admin_userid = $_COOKIE[$cookiepre."admin_userid"];
	$admin_username = $_COOKIE[$cookiepre."admin_username"];
	$admin_code = $_COOKIE[$cookiepre."admin_code"];
	//生成校验核对码
	$myvcode = md5($admin_userid.$admin_username.$admin_code.$key);
	$a2 = array($admin_userid,$admin_username,$admin_code,$myvcode);	
	$result=array_diff_assoc($a1,$a2);	
	if($result)
	{
		//核对不通过，提示登录
		//1403;
		header("Location:$admin_login");
		exit;
	}
}
else
{
	//数据不通过，提示登录
	//1404;
	header("Location:$admin_login");
	exit;
}