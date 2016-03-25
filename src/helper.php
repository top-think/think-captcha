<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

\think\Route::get('captcha/[:id]', ["\\think\\captcha\\CaptchaController", 'index']);

\think\Validate::extend('captcha', function ($value, $id = "") {
    $captcha = new \think\captcha\Captcha((array)\think\Config::get('captcha'));
    return $captcha->check($value, $id);
});

\think\Validate::setTypeMsg('captcha', '验证码错误!');