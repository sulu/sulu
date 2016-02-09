<?php

namespace Sulu\Component\Routing;

class UriUtils
{
    public static function relatizive($path)
    {
        while (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }

        return $path;
    }
}
