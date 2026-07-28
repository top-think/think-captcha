<?php

// +----------------------------------------------------------------------
// | ThinkPHP Captcha
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

use think\captcha\CaptchaService;
use think\facade\Route;
use think\Response;

/**
 * 生成验证码（返回数据，由开发者自行组装响应 / 加 CORS）
 *
 * @param string|null $config 配置分组名，对应 config('captcha.xxx')
 * @return array{key: string, img: string, expire: int}
 */
function captcha_generate(?string $config = null): array
{
    return CaptchaService::generate($config);
}

/**
 * 校验验证码
 *
 * @param string $key generate() 返回的 key
 * @param string $value 用户输入
 * @param bool $remove 校验成功后立即删除(默认 true，一次性)
 * @return bool
 */
function captcha_validate(string $key, string $value, bool $remove = true): bool
{
    return CaptchaService::validate($key, $value, $remove);
}

/**
 * 手动删除验证码（例如用户点击刷新图片）
 * @param string $key generate() 返回的 key
 */
function captcha_remove(string $key): void
{
    CaptchaService::remove($key);
}

// ========== 兼容：传统函数 ==========

/**
 * 直接返回验证码图片 Response（向后兼容，仅供参考）
 *
 * @param string|null $config
 * @return Response
 */
function captcha(string $config = null): Response
{
    $data = CaptchaService::generate($config);
    $b64  = substr($data['img'], (int)strpos($data['img'], ',') + 1);
    $bin  = base64_decode($b64, true) ?: '';

    return response($bin, 200, [
        'Content-Type'   => 'image/png',
        'Content-Length' => strlen($bin),
        'Cache-Control'  => 'no-store, no-cache, must-revalidate',
    ]);
}

/**
 * 生成验证码 URL（需要开发者自行注册路由，本扩展不自动挂载）
 * @param string|null $config
 * @return string
 */
function captcha_src(string $config = null): string
{
    return (string)Route::buildUrl('/captcha' . ($config ? "/{$config}" : ''));
}

/**
 * 生成验证码 <img> HTML（需要开发者自行注册路由，本扩展不自动挂载）
 * @param string $id
 * @param string $domid
 * @return string
 */
function captcha_img(string $id = '', string $domid = ''): string
{
    $src   = captcha_src($id);
    $domid = $domid === '' ? '' : "id='" . $domid . "'";

    return "<img src=\"$src\" alt='captcha' " . $domid . " onclick='this.src=\"$src?\"+Math.random();\"' />";
}

/**
 * 校验验证码（向后兼容）
 *  - 传 2 个参数： captcha_check($code, $key) 直接校验
 *  - 传 1 个参数： captcha_check($code) 会尝试从 request()->param('key') 取 key
 *
 * @param string $value
 * @param string|null $key
 * @return bool
 */
function captcha_check(string $value, string $key = null): bool
{
    if ($key === null || $key === '') {
        $key = function_exists('request')
            ? request()->param('key', request()->param('captcha_key', ''))
            : '';
    }
    if ($key === '') {
        return false;
    }

    return CaptchaService::validate((string)$key, (string)$value);
}
