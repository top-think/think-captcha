<?php

namespace think\facade;

class Route
{
    public static function buildUrl(string $url = ''): string
    {
        return '/index.php' . $url;
    }
}
