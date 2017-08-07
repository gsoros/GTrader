<?php

namespace GTrader;

use Illuminate\Support\Facades\Log as Monolog;

class Log {

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
        Monolog::{$name}(static::getCaller(), $args);
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
