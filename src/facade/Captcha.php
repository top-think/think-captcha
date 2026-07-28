<?php

namespace think\captcha\facade;

use think\Facade;

/**
 * 验证码 Facade（前后端分离 · 纯数据返回）
 *
 * @method static array generate(?string $config = null) 生成验证码，返回 [key, img, expire]
 * @method static bool  validate(string $key, string $code, bool $remove = true) 校验验证码
 * @method static void  remove(string $key) 手动删除验证码
 *
 * @package think\captcha\facade
 * @mixin \think\captcha\CaptchaService
 */
class Captcha extends Facade
{
    protected static function getFacadeClass()
    {
        return \think\captcha\CaptchaService::class;
    }
}
