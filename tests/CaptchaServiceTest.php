<?php

declare(strict_types=1);

namespace think\captcha\tests;

use PHPUnit\Framework\TestCase;
use think\captcha\CaptchaService;
use think\facade\Cache;
use think\facade\Config;

class CaptchaServiceTest extends TestCase
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
                'cachePrefix' => 'test_captcha_',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Cache::clear();
    }

    public function testGenerateReturnsExpectedStructure(): void
    {
        $result = CaptchaService::generate();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('key', $result);
        $this->assertArrayHasKey('img', $result);
        $this->assertArrayHasKey('expire', $result);
    }

    public function testGenerateImgIsValidBase64Png(): void
    {
        $result = CaptchaService::generate();
        $prefix = 'data:image/png;base64,';
        $this->assertStringStartsWith($prefix, $result['img']);
        $base64  = substr($result['img'], strlen($prefix));
        $decoded = base64_decode($base64, true);
        $this->assertNotFalse($decoded);
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $decoded);
    }

    public function testValidateWithCorrectCode(): void
    {
        $result   = CaptchaService::generate();
        $cacheKey = 'test_captcha_' . $result['key'];
        $code     = Cache::get($cacheKey);
        $this->assertIsString($code);
        $this->assertTrue(CaptchaService::validate($result['key'], $code));
    }

    public function testValidateCaseInsensitive(): void
    {
        $result   = CaptchaService::generate();
        $cacheKey = 'test_captcha_' . $result['key'];
        $code     = (string) Cache::get($cacheKey);
        $this->assertTrue(CaptchaService::validate($result['key'], strtoupper($code)));
    }

    public function testValidateWithWrongCode(): void
    {
        $result = CaptchaService::generate();
        $this->assertFalse(CaptchaService::validate($result['key'], 'wrong'));
    }

    public function testValidateWithEmptyKeyOrCode(): void
    {
        $this->assertFalse(CaptchaService::validate('', 'code'));
        $this->assertFalse(CaptchaService::validate('key', ''));
    }

    public function testValidateRemovesCacheByDefault(): void
    {
        $result   = CaptchaService::generate();
        $cacheKey = 'test_captcha_' . $result['key'];
        $code     = (string) Cache::get($cacheKey);
        CaptchaService::validate($result['key'], $code);
        $this->assertNull(Cache::get($cacheKey));
    }

    public function testValidateWithRemoveFalseKeepsCache(): void
    {
        $result   = CaptchaService::generate();
        $cacheKey = 'test_captcha_' . $result['key'];
        $code     = (string) Cache::get($cacheKey);
        CaptchaService::validate($result['key'], $code, false);
        $this->assertNotNull(Cache::get($cacheKey));
    }

    public function testRemoveDeletesCache(): void
    {
        $result   = CaptchaService::generate();
        $cacheKey = 'test_captcha_' . $result['key'];
        CaptchaService::remove($result['key']);
        $this->assertNull(Cache::get($cacheKey));
    }

    public function testGenerateWithMathConfig(): void
    {
        Config::set('captcha.math', true);
        $result   = CaptchaService::generate();
        $cacheKey = 'test_captcha_' . $result['key'];
        $answer   = Cache::get($cacheKey);
        $this->assertIsString($answer);
        $this->assertTrue(ctype_digit($answer));
    }
}
