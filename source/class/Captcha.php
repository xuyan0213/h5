<?php

namespace Gac163202505;

class Captcha
{
    protected $width;  //画布宽度
    protected $height; //画布高度
    protected $res;    //画布
    protected $len;    //验证码长度
    protected $code;   //验证码

    public function __construct($width = 150, $height = 45, $len = 4)
    {
        $this->width  = $width;
        $this->height = $height;
        $this->len    = $len;
    }

    public function render(): array
    {
        $res = imagecreatetruecolor($this->width, $this->height);
        imagefill($this->res = $res, 0, 0, imagecolorallocate($res, 200, 200, 200));
        $this->text();
        $this->line();
        $this->pix();
        return ['captcha' => $this->code, 'captcha_id' => $this->uuid()];
    }

    //生成uuid
    public function uuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }


    public function show()
    {
        ob_start();                        // 启动输出缓冲区
        header('Content-Type: image/png'); // 设置正确的 Content-Type 头
        imagepng($this->res);
        $content = ob_get_clean(); // 获取并清空输出缓冲区
        imagedestroy($this->res);

        return "data:image/png;base64," . base64_encode($content);
    }

    //生成随机码
    protected function text()
    {
        $font = dirname(__FILE__) . '/../../data/fonts/ggbi.ttf';
        $text = 'abcdefghjkmnpqrstuvwxyz123456789';
        for ($i = 0; $i < $this->len; $i++) {
            $x          = $this->width / $this->len;
            $angle      = mt_rand(-20, 20);
            $box        = imagettfbbox(20, $angle, $font, 'A');
            $this->code .= $code = strtoupper($text[mt_rand(0, strlen($text) - 1)]);
            imagettftext(
                $this->res,
                20,
                mt_rand(-20, 20),
                $x * $i + 10,
                $this->height / 2 - ($box[7] - $box[0]) / 2,
                $this->textColor(),
                $font,
                $code
            );
        };
    }

    //生成随机颜色的干扰线
    protected function line()
    {
        for ($i = 0; $i < 3; $i++) {
            imageline(
                $this->res,
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                $this->color()
            );
        }
    }

    //生成随机颜色
    protected function color()
    {
        return imagecolorallocate($this->res, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
    }

    protected function textColor()
    {
        return imagecolorallocate($this->res, mt_rand(0, 100), mt_rand(0, 100), mt_rand(0, 100));
    }

    //绘制干扰点
    protected function pix()
    {
        for ($i = 0; $i < 50; $i++) {
            imagesetpixel($this->res, mt_rand(0, $this->width), mt_rand(0, $this->height), $this->color());
        }
    }

}