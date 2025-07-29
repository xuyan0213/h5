<?php

namespace Gac163202505;

class Sign
{
    private $secret = 'insun@2022';

    /**
     * 设置签名
     * @param $data
     * @return string
     */
    public function setSign($data): string
    {
        $aes = new Aes(substr(md5($this->secret), 0, 16), substr(md5($this->secret), -16));
        // 删除空值
        $data = array_filter($data, function ($v) {
            return $v !== '';
        });
        // 按字段排序
        ksort($data);
        //拼接字符串数据(修复空格会替换为+号)
        $string = http_build_query($data, null, '&', PHP_QUERY_RFC3986);
        //通过 aes 来加密
        if (empty($string)) {
            $string = 'secret='. $this->secret;
        } else {
            $string .= '&secret=' . $this->secret;
        }
        return $aes->encrypt($string);
    }

    /**
     * 验证签名
     * @param $data
     * @return bool
     */
    public function checkSign($data): bool
    {

        $sign = !empty($_SERVER['HTTP_SIGNATURE']) ? $_SERVER['HTTP_SIGNATURE'] : '';

        if (empty($sign)) {
            return false;
        }
        $setSign = $this->setSign($data);
        if ($setSign != $sign) {
            return false;
        }
        $aes = new Aes(substr(md5($this->secret), 0, 16), substr(md5($this->secret), -16));

        $str = $aes->decrypt($sign);

        // 判断解析出来的数据是否为空
        if (empty($str)) {
            return false;
        }
        // 字符串转数组
        parse_str($str, $arr);
        // 判断是否是数组,数组内的字段是否正确
        if (!is_array($arr)) {
            return false;
        }
        return true;
    }
}