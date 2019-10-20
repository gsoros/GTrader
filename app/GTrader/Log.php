<?php

namespace GTrader;

class Log extends Base {

    /*
    Log::emergency(... $messages);
    Log::alert();
    Log::critical();
    Log::error();
    Log::warning();
    Log::notice();
    Log::info();
    Log::debug();
    Log::sparse();
    */


    public static function __callStatic($severity, $args)
    {
        return static::output(
            static::getMessage(
                config('app.env').'.'.
                    strtoupper($severity).' '.
                    static::getCaller(),
                $args
            )
        );
    }


    public static function sparse(... $args)
    {
        return static::output(static::getMessage($args));
    }


    protected static function output(
        string $message = '',
        string $prefix = '',
        bool $flush = false): bool
    {
        $singleton = self::singleton();
        $repeat_counter = $singleton->repeatCounter($message);

        if ($flush) {
            if (strlen($message)) {
                $message = ($prefix ? $prefix.' ' : '') . "\n";
            }
            $message .= $singleton->getParam('last_message');
        }
        else {
            $prefix = strlen($prefix) ? $prefix.' ' : $prefix;
            $message = $prefix.$message;
        }

        if (!$repeat_counter && !$flush) {
            return true;
        }
        if (1 < $repeat_counter) {
            $times = 2 < $repeat_counter ? ($repeat_counter - 1).' times' : 'once';
            error_log(
                ($prefix ? $prefix.' ' : '') .
                    'Last message repeated '.$times."\n",
                $singleton->getParam('message_type'),
                $singleton->getParam('destination')
            );
        }
        if (strlen($message)) {
            return error_log(
                '['.date('Y-m-d H:i:s').'] '.$message."\n",
                $singleton->getParam('message_type'),
                $singleton->getParam('destination')
            );
        }
        return true;
    }


    public function __destruct()
    {
        static::output('', '', true); // flush
        parent::__destruct();
    }


    protected function repeatCounter(string $message): int
    {
        if ($message == $this->getParam('last_message')) {
            $this->setParam('repeat_count', $this->getParam('repeat_count', 1) + 1);
            return 0;
        }
        $this->setParam('last_message', $message);
        $return = $this->getParam('repeat_count', 1);
        $this->setParam('repeat_count', 1);
        return $return;
    }


    protected static function getMessage(... $args): string
    {
        return join(' | ', array_map(function($v) {
            return substr(stripslashes(json_encode($v)), 1, -1);
        }, $args));
    }


    protected static function getCaller(): string
    {
        list($na, $file, $class) = debug_backtrace(false, 3);

        $args = implode(', ', array_map(function($v) {
            return gettype($v);
        }, $class['args'] ?? []));

        $type = $class['type'] ?? '';
        $function = $class['function'] ?? '';
        $class = $class['class'] ?? '';
        $line = $file['line'] ?? '';
        $file = $file['file'] ?? '';

        return '['.basename($file).':'.$line.'] ['.
            substr($class, strrpos($class, '\\') + 1).
            $type.$function.'('.$args.')]';

    }
}
