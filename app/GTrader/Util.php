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
}
