<?php

namespace GTrader;

class Util
{
    public static function db_escape($string)
    {
        return app('db')->getPdo()->quote($string);
    }
    
    public static function dump($v)
    {
        echo static::getDump($v);
    }
    
    public static function getDump($v)
    {
        return var_export($v, true);
    }
}
