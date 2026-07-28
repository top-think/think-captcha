<?php

require_once __DIR__ . '/../vendor/autoload.php';

use think\contract\SessionHandlerInterface;

class TestSessionHandler implements SessionHandlerInterface
{
    protected array $data = [];

    public function read(string $sessionId): string
    {
        return $this->data[$sessionId] ?? '';
    }

    public function delete(string $sessionId): bool
    {
        unset($this->data[$sessionId]);

        return true;
    }

    public function write(string $sessionId, string $data): bool
    {
        $this->data[$sessionId] = $data;

        return true;
    }
}

if (!class_exists('think\facade\Cache', false)) {
    require_once __DIR__ . '/mocks/CacheFacade.php';
}

if (!class_exists('think\facade\Config', false)) {
    require_once __DIR__ . '/mocks/ConfigFacade.php';
}

if (!class_exists('think\facade\Route', false)) {
    require_once __DIR__ . '/mocks/RouteFacade.php';
}

if (!function_exists('request')) {
    function request()
    {
        global $mockRequest;

        return $mockRequest ?? new class () {
            public function param($name = null, $default = null)
            {
                if (is_array($name)) {
                    return $name;
                }

                return $default;
            }
        };
    }
}

if (!function_exists('response')) {
    function response($data = '', $code = 200, $header = [], $type = 'html'): \think\Response
    {
        return \think\Response::create($data, $type, $code)->header($header);
    }
}
