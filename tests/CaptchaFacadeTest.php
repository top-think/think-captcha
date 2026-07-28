<?php

declare(strict_types=1);

namespace think\captcha\tests;

use PHPUnit\Framework\TestCase;
use think\captcha\facade\Captcha;
use think\facade\Cache;
use think\facade\Config;

class CaptchaFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        Cache::clear();
        Config::set([
            'captcha' => [
                'length'      => 4,
                'expire'      => 120,
                'math'        => false,
                'useNoise'    => false,
                'useCurve'    => false,
                'useImgBg'    => false,
                'fontSize'    => 20,
                'cachePrefix' => 'test_facade_',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Cache::clear();
    }

    public function testFacadeGenerate(): void
    {
        $result = Captcha::generate();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('key', $result);
        $this->assertArrayHasKey('img', $result);
        $this->assertArrayHasKey('expire', $result);
    }

    public function testFacadeValidate(): void
    {
        $result   = Captcha::generate();
        $cacheKey = 'test_facade_' . $result['key'];
        $code     = (string) Cache::get($cacheKey);
        $this->assertTrue(Captcha::validate($result['key'], $code));
    }

    public function testFacadeValidateWrong(): void
    {
        $result = Captcha::generate();
        $this->assertFalse(Captcha::validate($result['key'], 'wrong'));
    }

    public function testFacadeRemove(): void
    {
        $result   = Captcha::generate();
        $cacheKey = 'test_facade_' . $result['key'];
        Captcha::remove($result['key']);
        $this->assertNull(Cache::get($cacheKey));
    }
}
