<?php

// +----------------------------------------------------------------------
// | Captcha配置文件
// +----------------------------------------------------------------------

return [
    //验证码位数
    'length'      => 4,
    // 字符集合（默认已剔除 0/O/1/I 等易混淆字符）
    'codeSet'     => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
    // 过期时间（秒）
    'expire'      => 180,
    // 算术验证码 (true 时 length / codeSet 忽略)
    'math'        => false,
    // 画干扰点
    'useNoise'    => true,
    // 画干扰线
    'useCurve'    => true,
    // 使用 assets/bgs/ 下的图片做背景（平铺拉伸到验证码尺寸，true 时忽略 bg 颜色）
    'useImgBg'    => false,
    // 背景色 [R, G, B]（useImgBg=false 或背景图加载失败时生效）
    'bg'          => [243, 251, 254],
    // 图片尺寸（填 0 则根据 fontSize 自动计算）
    'imageH'      => 0,
    'imageW'      => 0,
    // 字号（px）
    'fontSize'    => 22,
    // 缓存键前缀（多项目共用 Redis 时改一改防冲突）
    'cachePrefix' => 'captcha_',
];
