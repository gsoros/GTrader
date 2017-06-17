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

    public static function humanBytes($bytes)
    {
        $unit = ['B','kB','MB','GB','TB','PB', 'EB', 'ZB', 'YB'];
        return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), 2).$unit[$i];
    }

    public static function getMemoryUsage(bool $real_usage = false)
    {
        return self::humanBytes(memory_get_usage($real_usage));

    }

}
