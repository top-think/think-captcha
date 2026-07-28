<?php

namespace think\facade;

class Cache
{
    protected static array $data = [];
    protected static array $ttl  = [];

    public static function set(string $key, $value, $ttl = null): bool
    {
        self::$data[$key] = $value;
        self::$ttl[$key]  = $ttl;

        return true;
    }

    public static function get(string $key, $default = null)
    {
        if (array_key_exists($key, self::$data)) {
            return self::$data[$key];
        }

        return $default;
    }

    public static function delete(string $key): bool
    {
        unset(self::$data[$key], self::$ttl[$key]);

        return true;
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::$data);
    }

    public static function clear(): bool
    {
        self::$data = [];
        self::$ttl  = [];

        return true;
    }
}
