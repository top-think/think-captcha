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

think\facade\Validate::extend('captcha', function ($value, $id = '') {
    return captcha_check($value, $id);
});

think\facade\Validate::setTypeMsg('captcha', ':attribute错误!');

/**
 * @param string $id
 * @param array  $config
 * @return \think\Response
 */
function captcha($id = '', $config = [])
{
    $captcha = new \think\captcha\Captcha($config);
    return $captcha->entry($id);
}

/**
 * @param $id
 * @return string
 */
function captcha_src($id = '')
{
    return think\facade\Url::build('/captcha/get_validate_code/' . ($id ? "/{$id}" : ''),[],'','api');
}

/**
 * @param $id
 * @return mixed
 */
function captcha_img($id = '')
{
    return '<img src="' . captcha_src($id) . '" alt="captcha" />';
}

/**
 * @param string $id
 * @param string $element 验证码HTML元素ID
 * @return string
 */
function captcha_img_with_replacement($id = '', $element = 'alimx-captcha')
{
    return '<img src="' . captcha_src($id) . '" alt="captcha" class="' . $element . '" onclick="this.src=\''.captcha_src($id).'?\' + Math.random();" />';
}

/**
 * @param        $value
 * @param string $id
 * @param array  $config
 * @return bool
 */
function captcha_check($value, $id = '')
{
    $captcha = new \think\captcha\Captcha((array) \think\facade\Config::pull('captcha'));
    return $captcha->check($value, $id);
}
