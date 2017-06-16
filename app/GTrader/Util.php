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

    public static function logTrace()
    {
        $e = new \Exception;
        error_log("-----Backtrace:\n".
                    var_export($e->getTraceAsString(), true).
                    "\n----End Backtrace");
    }

    public static function getMemoryUsage()
    {
        $unit = ['b','kb','mb','gb','tb','pb'];
        $size = memory_get_usage(true);
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2).' '.$unit[$i];
    }

}
