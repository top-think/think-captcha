<?php

namespace think\facade;

class Config
{
    protected static array $config = [];

    public static function set($name, $value = null): void
    {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                self::set($k, $v);
            }
        } else {
            $keys = explode('.', strtolower($name));
            $ref  = &self::$config;
            foreach ($keys as $k) {
                if (!isset($ref[$k]) || !is_array($ref[$k])) {
                    $ref[$k] = [];
                }
                $ref = &$ref[$k];
            }
            $ref = $value;
        }
    }

    public static function get(?string $name = null, $default = null)
    {
        if ($name === null) {
            return self::$config;
        }
        $keys = explode('.', strtolower($name));
        $ref  = self::$config;
        foreach ($keys as $k) {
            if (!is_array($ref) || !array_key_exists($k, $ref)) {
                return $default;
            }
            $ref = $ref[$k];
        }

        return $ref;
    }

    public static function has(string $name): bool
    {
        return self::get($name) !== null;
    }
}
