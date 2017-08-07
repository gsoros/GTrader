<?php

namespace GTrader;

class Log {

    protected static $logger = 'Illuminate\\Support\\Facades\\Log';

    /*
    Log::emergency(... $messages);
    Log::alert();
    Log::critical();
    Log::error();
    Log::warning();
    Log::notice();
    Log::info();
    Log::debug();
    */

    public static function __callStatic($name, $args)
    {
        return forward_static_call_array([static::$logger, $name], $args);
    }

    public static function sparse(... $args)
    {
        //return forward_static_call_array([static::$logger, 'info'], $args);
        return error_log('['.date('Y-m-d H:i:s').'] '.join(', ', array_map(function($v) {
            return json_encode($v);
        }, $args)));
    }

    protected static function getCaller()
    {
        list($na, $file, $class) = debug_backtrace(false, 3);

        $args = implode(', ', array_map(function($v) {
            return gettype($v);
        }, $class['args']));

        return '['.basename($file['file']).':'.$file['line'].'] ['.
            substr($class['class'], strrpos($class['class'], '\\') + 1).
            $class['type'].$class['function'].'('.$args.')]';

    }
}
