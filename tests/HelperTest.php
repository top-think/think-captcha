<?php

declare(strict_types=1);

namespace think\captcha\tests;

use PHPUnit\Framework\TestCase;
use think\facade\Cache;
use think\facade\Config;

class HelperTest extends TestCase
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
                'cachePrefix' => 'test_helper_',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Cache::clear();
    }

    public function testCaptchaGenerateFunction(): void
    {
        $result = captcha_generate();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('key', $result);
        $this->assertArrayHasKey('img', $result);
        $this->assertArrayHasKey('expire', $result);
    }

    public function testCaptchaGenerateImgValid(): void
    {
        $result = captcha_generate();
        $this->assertStringStartsWith('data:image/png;base64,', $result['img']);
    }

    public function testCaptchaValidateFunction(): void
    {
        $result   = captcha_generate();
        $cacheKey = 'test_helper_' . $result['key'];
        $code     = (string) Cache::get($cacheKey);
        $this->assertTrue(captcha_validate($result['key'], $code));
    }

    public function testCaptchaValidateWrongCode(): void
    {
        $result = captcha_generate();
        $this->assertFalse(captcha_validate($result['key'], 'wrong-code'));
    }

    public function testCaptchaValidateRemoveFalse(): void
    {
        $result   = captcha_generate();
        $cacheKey = 'test_helper_' . $result['key'];
        $code     = (string) Cache::get($cacheKey);
        captcha_validate($result['key'], $code, false);
        $this->assertNotNull(Cache::get($cacheKey));
    }

    public function testCaptchaRemoveFunction(): void
    {
        $result   = captcha_generate();
        $cacheKey = 'test_helper_' . $result['key'];
        captcha_remove($result['key']);
        $this->assertNull(Cache::get($cacheKey));
    }

    public function testCaptchaSrcReturnsUrl(): void
    {
        $url = captcha_src();
        $this->assertNotEmpty($url);
        $this->assertStringContainsString('/captcha', $url);
    }

    public function testCaptchaSrcWithConfig(): void
    {
        $url = captcha_src('login');
        $this->assertStringContainsString('/captcha/login', $url);
    }

    public function testCaptchaImgReturnsHtml(): void
    {
        $html = captcha_img();
        $this->assertStringStartsWith('<img ', $html);
        $this->assertStringContainsString('captcha', $html);
        $this->assertStringContainsString('onclick=', $html);
    }

    public function testCaptchaImgWithDomId(): void
    {
        $html = captcha_img('', 'mycaptcha');
        $this->assertStringContainsString("id='mycaptcha'", $html);
    }

    public function testCaptchaImgWithConfig(): void
    {
        $html = captcha_img('login');
        $this->assertStringContainsString('/captcha/login', $html);
    }

    public function testCaptchaFunctionReturnsResponse(): void
    {
        $response = captcha();
        $this->assertInstanceOf(\think\Response::class, $response);
    }

    public function testCaptchaCheckWithKey(): void
    {
        $result   = captcha_generate();
        $cacheKey = 'test_helper_' . $result['key'];
        $code     = (string) Cache::get($cacheKey);
        $this->assertTrue(captcha_check($code, $result['key']));
    }

    public function testCaptchaCheckWithWrongKey(): void
    {
        $this->assertFalse(captcha_check('code', 'non-existent'));
    }

    public function testCaptchaCheckEmptyKey(): void
    {
        $this->assertFalse(captcha_check('code'));
    }
}
