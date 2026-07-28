<?php

declare(strict_types=1);

namespace think\captcha\tests;

use Mockery;
use PHPUnit\Framework\TestCase;
use think\Config;
use think\Session;
use think\captcha\Captcha;

class CaptchaTest extends TestCase
{
    private Config $config;
    private object $sessionData;

    protected function setUp(): void
    {
        $this->config = new Config();
        $this->config->set([
            'captcha' => [
                'length'   => 4,
                'expire'   => 1800,
                'math'     => false,
                'useZh'    => false,
                'useImgBg' => false,
                'useCurve' => false,
                'useNoise' => false,
                'fontSize' => 20,
                'api'      => false,
                'bg'       => [243, 251, 254],
                'imageH'   => 0,
                'imageW'   => 0,
            ],
        ]);

        $data              = [];
        $this->sessionData = new class ($data) {
            private array $data;
            public function __construct(array &$ref)
            {
                $this->data = &$ref;
            }
            public function set(string $name, $value): void
            {
                $keys = explode('.', $name);
                $ref  = &$this->data;
                foreach ($keys as $k) {
                    if (!isset($ref[$k]) || !is_array($ref[$k])) {
                        $ref[$k] = [];
                    }
                    $ref = &$ref[$k];
                }
                $ref = $value;
            }
            public function get(string $name, $default = null)
            {
                $keys = explode('.', $name);
                $ref  = $this->data;
                foreach ($keys as $k) {
                    if (!is_array($ref) || !array_key_exists($k, $ref)) {
                        return $default;
                    }
                    $ref = $ref[$k];
                }

                return $ref;
            }
            public function has(string $name): bool
            {
                return $this->get($name) !== null;
            }
            public function delete(string $name): void
            {
                $keys = explode('.', $name);
                $last = array_pop($keys);
                $ref  = &$this->data;
                foreach ($keys as $k) {
                    if (!isset($ref[$k]) || !is_array($ref[$k])) {
                        return;
                    }
                    $ref = &$ref[$k];
                }
                unset($ref[$last]);
            }
        };
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function makeCaptcha(): Captcha
    {
        $sd   = $this->sessionData;
        $mock = Mockery::mock(Session::class);
        $mock->shouldReceive('set')->andReturnUsing(function ($n, $v) use ($sd) {
            $sd->set($n, $v);
        });
        $mock->shouldReceive('get')->andReturnUsing(function ($n, $d = null) use ($sd) {
            return $sd->get($n, $d);
        });
        $mock->shouldReceive('has')->andReturnUsing(function ($n) use ($sd) {
            return $sd->has($n);
        });
        $mock->shouldReceive('delete')->andReturnUsing(function ($n) use ($sd) {
            $sd->delete($n);
        });

        return new Captcha($this->config, $mock);
    }

    public function testCheckWithoutCaptchaInSession(): void
    {
        $captcha = $this->makeCaptcha();
        $this->assertFalse($captcha->check('anything'));
    }

    public function testCheckWithIncorrectCode(): void
    {
        $this->sessionData->set('captcha', [
            'key' => password_hash('correct', PASSWORD_BCRYPT, ['cost' => 10]),
        ]);
        $captcha = $this->makeCaptcha();
        $this->assertFalse($captcha->check('wrong'));
    }

    public function testCheckWithCorrectCode(): void
    {
        $this->sessionData->set('captcha', [
            'key' => password_hash('hello', PASSWORD_BCRYPT, ['cost' => 10]),
        ]);
        $captcha = $this->makeCaptcha();
        $this->assertTrue($captcha->check('hello'));
    }

    public function testCheckIsCaseInsensitive(): void
    {
        $this->sessionData->set('captcha', [
            'key' => password_hash('hello', PASSWORD_BCRYPT, ['cost' => 10]),
        ]);
        $captcha = $this->makeCaptcha();
        $this->assertTrue($captcha->check('HELLO'));
    }

    public function testCheckDeletesSessionOnSuccess(): void
    {
        $this->sessionData->set('captcha', [
            'key' => password_hash('test', PASSWORD_BCRYPT, ['cost' => 10]),
        ]);
        $captcha = $this->makeCaptcha();
        $captcha->check('test');
        $this->assertFalse($this->sessionData->has('captcha'));
    }

    public function testCheckKeepsSessionOnFailure(): void
    {
        $this->sessionData->set('captcha', [
            'key' => password_hash('test', PASSWORD_BCRYPT, ['cost' => 10]),
        ]);
        $captcha = $this->makeCaptcha();
        $captcha->check('wrong');
        $this->assertTrue($this->sessionData->has('captcha'));
    }

    public function testCreateApiModeReturnsArray(): void
    {
        $this->config->set(['captcha' => ['api' => true, 'length' => 4, 'fontSize' => 20, 'useCurve' => false, 'useNoise' => false, 'useImgBg' => false]], null);
        $captcha = $this->makeCaptcha();
        $result  = $captcha->create();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('img', $result);
    }

    public function testCreateApiModeImgIsValidDataUri(): void
    {
        $this->config->set(['captcha' => ['api' => true, 'length' => 4, 'fontSize' => 20, 'useCurve' => false, 'useNoise' => false, 'useImgBg' => false]], null);
        $captcha = $this->makeCaptcha();
        $result  = $captcha->create();
        $prefix  = 'data:image/png;base64,';
        $this->assertStringStartsWith($prefix, $result['img']);
        $decoded = base64_decode(substr($result['img'], strlen($prefix)), true);
        $this->assertNotFalse($decoded);
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $decoded);
    }

    public function testCreateStoresCaptchaInSession(): void
    {
        $this->config->set(['captcha' => ['api' => true, 'length' => 4, 'fontSize' => 20, 'useCurve' => false, 'useNoise' => false, 'useImgBg' => false]], null);
        $captcha = $this->makeCaptcha();
        $captcha->create();
        $this->assertTrue($this->sessionData->has('captcha'));
    }

    public function testApiCreateCheckRoundTrip(): void
    {
        $this->config->set(['captcha' => ['api' => true, 'length' => 4, 'fontSize' => 20, 'useCurve' => false, 'useNoise' => false, 'useImgBg' => false, 'math' => false]], null);
        $captcha = $this->makeCaptcha();
        $result  = $captcha->create();
        $code    = mb_strtolower($result['code'], 'UTF-8');
        $this->assertTrue($captcha->check($code));
    }
}
