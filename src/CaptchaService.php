<?php

// +----------------------------------------------------------------------
// | ThinkPHP Captcha (前后端分离 · 纯服务类)
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace think\captcha;

use think\facade\Cache;
use think\facade\Config;

/**
 * 验证码服务（纯数据返回，开发者自行处理路由 / 响应 / CORS）
 * */
class CaptchaService
{
    // ========== 默认兜底常量 ==========
    public const string DEFAULT_CHARS        = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    public const string DEFAULT_CACHE_PREFIX = 'captcha_';
    public const int DEFAULT_EXPIRE          = 120;
    public const int DEFAULT_LENGTH          = 4;
    public const int DEFAULT_FONT_SIZE       = 22;

    /**
     * 生成验证码
     *
     * @param string|null $config 配置分组名（对应 config/captcha.php 中的二级数组，如 login/register），传 null 读根配置
     * @return array{key: string, img: string, expire: int}
     */
    public static function generate(?string $config = null): array
    {
        $cfg = self::readConfig($config);

        $key  = md5(uniqid((string)mt_rand(), true));
        $code = self::makeCode($cfg);

        // 存缓存
        $cacheKey = $cfg['cachePrefix'] . $key;
        Cache::set($cacheKey, $code['answer'], $cfg['expire']);

        // 生成图片
        $img = self::makeImage($code['display'], $cfg);

        return [
            'key'    => $key,
            'img'    => $img,
            'expire' => $cfg['expire'],
        ];
    }

    /**
     * 校验验证码
     *
     * @param string $key generate() 返回的 key
     * @param string $code 用户输入
     * @param bool $remove 校验成功后是否立即删除（一次性，默认 true）
     * @return bool
     */
    public static function validate(string $key, string $code, bool $remove = true): bool
    {
        if ($key === '' || $code === '') {
            return false;
        }
        $cfg      = self::readConfig(null);
        $cacheKey = $cfg['cachePrefix'] . $key;
        $stored   = Cache::get($cacheKey);
        if ($stored === null || $stored === false || $stored === '') {
            return false;
        }
        $ok = strcasecmp((string)$stored, trim($code)) === 0;
        if ($ok && $remove) {
            Cache::delete($cacheKey);
        }

        return $ok;
    }

    /**
     * 手动删除验证码（例如用户点击刷新图片）
     * @param string $key
     */
    public static function remove(string $key): void
    {
        if ($key === '') {
            return;
        }
        $cfg      = self::readConfig(null);
        $cacheKey = $cfg['cachePrefix'] . $key;
        Cache::delete($cacheKey);
    }

    /**
     * 读取并规范化配置
     * @param string|null $name 子配置名
     * @return array
     */
    private static function readConfig(?string $name): array
    {
        $root = Config::get('captcha', []);
        $cfg  = is_array($root) ? $root : [];

        if ($name !== null && $name !== '' && isset($cfg[$name]) && is_array($cfg[$name])) {
            $cfg = array_merge($cfg, $cfg[$name]);
        }

        $defaults = [
            'length'      => self::DEFAULT_LENGTH,
            'codeSet'     => self::DEFAULT_CHARS,
            'expire'      => self::DEFAULT_EXPIRE,
            'math'        => false,
            'useNoise'    => true,
            'useCurve'    => true,
            'useImgBg'    => false,
            'bg'          => [243, 251, 254],
            'imageH'      => 0,
            'imageW'      => 0,
            'fontSize'    => self::DEFAULT_FONT_SIZE,
            'cachePrefix' => self::DEFAULT_CACHE_PREFIX,
        ];

        $r = [];
        foreach ($defaults as $k => $v) {
            $r[$k] = $cfg[$k] ?? $v;
        }

        // 规范化
        $r['length']      = max(1, (int)$r['length']);
        $r['expire']      = max(10, (int)$r['expire']);
        $r['fontSize']    = max(10, (int)$r['fontSize']);
        $r['math']        = (bool)$r['math'];
        $r['useNoise']    = (bool)$r['useNoise'];
        $r['useCurve']    = (bool)$r['useCurve'];
        $r['useImgBg']    = (bool)$r['useImgBg'];
        $r['cachePrefix'] = is_string($r['cachePrefix']) && $r['cachePrefix'] !== '' ? $r['cachePrefix'] : self::DEFAULT_CACHE_PREFIX;
        $r['codeSet']     = is_string($r['codeSet'])     && $r['codeSet']         !== '' ? $r['codeSet'] : self::DEFAULT_CHARS;
        if (!is_array($r['bg']) || count($r['bg']) < 3) {
            $r['bg'] = [243, 251, 254];
        }

        // 自动计算图片尺寸
        if ((int)$r['imageW'] <= 0) {
            $r['imageW'] = (int)($r['fontSize'] * ($r['math'] ? 7 : ($r['length'] + 2)));
        }
        if ((int)$r['imageH'] <= 0) {
            $r['imageH'] = (int)($r['fontSize'] * 2.2);
        }
        $r['imageW'] = max(60, (int)$r['imageW']);
        $r['imageH'] = max(24, (int)$r['imageH']);

        return $r;
    }

    // ========== 内部: 生成验证码文本 ==========

    /**
     * @return array{display: string, answer: string}
     */
    private static function makeCode(array $cfg): array
    {
        if ($cfg['math']) {
            $a   = random_int(1, 20);
            $b   = random_int(1, 9);
            $ops = ['+', '-', 'x'];
            $op  = $ops[array_rand($ops)];
            switch ($op) {
                case '-':
                    if ($a < $b) {
                        [$a, $b] = [$b, $a];
                    }
                    $ans = $a - $b;
                    break;
                case 'x':
                    $b   = random_int(1, 5);
                    $ans = $a * $b;
                    break;
                default:
                    $ans = $a + $b;
                    break;
            }

            return [
                'display' => "{$a}{$op}{$b}=",
                'answer'  => (string)$ans,
            ];
        }

        $chars = $cfg['codeSet'];
        $max   = strlen($chars) - 1;
        $code  = '';
        for ($i = 0; $i < $cfg['length']; $i++) {
            $code .= $chars[mt_rand(0, $max)];
        }

        return [
            'display' => $code,
            'answer'  => mb_strtolower($code, 'UTF-8'),
        ];
    }

    /**
     * @param string $text 显示的文字
     * @param array $cfg
     * @return string data:image/png;base64,...
     */
    private static function makeImage(string $text, array $cfg): string
    {
        $w = $cfg['imageW'];
        $h = $cfg['imageH'];

        $img = imagecreatetruecolor($w, $h);

        if ($cfg['useImgBg']) {
            $bgFile = self::pickBackground();
            if ($bgFile !== null) {
                $srcInfo = @getimagesize($bgFile);
                if ($srcInfo !== false && isset($srcInfo[0], $srcInfo[1], $srcInfo[2]) && $srcInfo[0] > 0 && $srcInfo[1] > 0) {
                    [$srcW, $srcH, $srcType] = $srcInfo;
                    $srcImg                  = null;
                    switch ($srcType) {
                        case IMAGETYPE_JPEG:
                            $srcImg = @imagecreatefromjpeg($bgFile);
                            break;
                        case IMAGETYPE_PNG:
                            $srcImg = @imagecreatefrompng($bgFile);
                            break;
                        case IMAGETYPE_GIF:
                            $srcImg = @imagecreatefromgif($bgFile);
                            break;
                    }
                    if ($srcImg !== false && $srcImg !== null) {
                        imagecopyresampled($img, $srcImg, 0, 0, 0, 0, $w, $h, $srcW, $srcH);
                        imagedestroy($srcImg);
                    } else {
                        self::fillSolidColor($img, $cfg['bg']);
                    }
                } else {
                    self::fillSolidColor($img, $cfg['bg']);
                }
            } else {
                self::fillSolidColor($img, $cfg['bg']);
            }
        } else {
            self::fillSolidColor($img, $cfg['bg']);
        }

        // 干扰点
        if ($cfg['useNoise']) {
            $n = (int)($w * $h / 90);
            for ($i = 0; $i < $n; $i++) {
                $c = imagecolorallocate($img, mt_rand(120, 220), mt_rand(120, 220), mt_rand(120, 220));
                imagesetpixel($img, mt_rand(0, $w - 1), mt_rand(0, $h - 1), $c);
            }
        }

        // 干扰线
        if ($cfg['useCurve']) {
            for ($i = 0; $i < 2; $i++) {
                $c  = imagecolorallocate($img, mt_rand(120, 200), mt_rand(120, 200), mt_rand(120, 200));
                $x1 = mt_rand(0, (int)($w * 0.25));
                $y1 = mt_rand(0, $h - 1);
                $x2 = mt_rand((int)($w * 0.75), $w - 1);
                $y2 = mt_rand(0, $h - 1);
                $mx = (int)(($x1 + $x2) / 2 + mt_rand(-10, 10));
                $my = (int)(($y1 + $y2) / 2 + mt_rand(-8, 8));
                imageline($img, $x1, $y1, $mx, $my, $c);
                imageline($img, $mx, $my, $x2, $y2, $c);
            }
        }

        // 字体
        $font  = self::pickFont();
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $cnt   = max(1, count($chars));
        $step  = $w / ($cnt + 1);
        $x0    = $step;

        foreach ($chars as $idx => $ch) {
            $c  = imagecolorallocate($img, mt_rand(20, 120), mt_rand(20, 120), mt_rand(40, 140));
            $x  = (int)($x0 + $idx * $step + mt_rand(-2, 2));
            $y  = (int)($h * 0.72 + mt_rand(-3, 3));
            $a  = mt_rand(-15, 15);
            $fs = $cfg['fontSize'];
            if ($font !== null) {
                imagettftext($img, $fs, $a, $x, $y, $c, $font, $ch);
            } else {
                imagestring($img, 5, $x - 8, $y - 15, $ch, $c);
            }
        }

        ob_start();
        imagepng($img);
        $bin = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($bin);
    }

    /**
     * 纯色填充背景（背景图加载失败 / 未开启 useImgBg 时兜底）
     * @param resource $img
     * @param array $bg [R, G, B]
     */
    private static function fillSolidColor($img, array $bg): void
    {
        [$br, $bbg, $bb] = $bg;
        imagefill($img, 0, 0, imagecolorallocate($img, (int)$br, (int)$bbg, (int)$bb));
    }

    /**
     * 从 assets/ttfs 与 assets/zhttfs 随机挑一个可读字体
     * @return string|null
     */
    private static function pickFont(): ?string
    {
        $dirs  = [__DIR__ . '/../assets/ttfs/', __DIR__ . '/../assets/zhttfs/'];
        $fonts = [];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach (scandir($dir) as $f) {
                if ($f === '.' || $f === '..') {
                    continue;
                }
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                if (!in_array($ext, ['ttf', 'otf'], true)) {
                    continue;
                }
                $full = $dir . $f;
                if (is_readable($full)) {
                    $fonts[] = $full;
                }
            }
        }

        return $fonts === [] ? null : $fonts[array_rand($fonts)];
    }

    /**
     * 从 assets/bgs 随机挑一张可读背景图（支持 jpg/jpeg/png/gif）
     * @return string|null
     */
    private static function pickBackground(): ?string
    {
        $dir = __DIR__ . '/../assets/bgs/';
        if (!is_dir($dir)) {
            return null;
        }
        $bgs = [];
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                continue;
            }
            $full = $dir . $f;
            if (is_readable($full)) {
                $bgs[] = $full;
            }
        }

        return $bgs === [] ? null : $bgs[array_rand($bgs)];
    }
}
