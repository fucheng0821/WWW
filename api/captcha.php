<?php
session_start();

// 生成验证码
function generateCaptcha($length = 4) {
    $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $captcha = '';
    for ($i = 0; $i < $length; $i++) {
        $captcha .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $captcha;
}

// 创建验证码图片
function createCaptchaImage($captcha) {
    $width = 120;
    $height = 40;
    
    // 创建图像
    $image = imagecreate($width, $height);
    
    // 设置颜色
    $bgColor = imagecolorallocate($image, 245, 245, 245);
    $textColor = imagecolorallocate($image, rand(0, 100), rand(0, 100), rand(0, 100));
    $lineColor = imagecolorallocate($image, rand(200, 255), rand(200, 255), rand(200, 255));
    
    // 填充背景
    imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
    
    // 添加干扰线
    for ($i = 0; $i < 5; $i++) {
        imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
    }
    
    // 添加验证码文字
    $font = 5;
    $x = ($width - imagefontwidth($font) * strlen($captcha)) / 2;
    $y = ($height - imagefontheight($font)) / 2;
    imagestring($image, $font, $x, $y, $captcha, $textColor);
    
    // 添加干扰点
    for ($i = 0; $i < 50; $i++) {
        imagesetpixel($image, rand(0, $width), rand(0, $height), $textColor);
    }
    
    return $image;
}

// 生成验证码
$captcha = generateCaptcha();
$_SESSION['captcha'] = $captcha;

// 创建并输出验证码图片
header('Content-Type: image/png');
$image = createCaptchaImage($captcha);
imagepng($image);
imagedestroy($image);
?>