<?php

namespace GTrader;

class Util
{
    public static function ksortR(&$array, $sort_flags = SORT_REGULAR)
    {
        if (!is_array($array)) {
            return false;
        }
        ksort($array, $sort_flags);
        foreach ($array as &$arr) {
            self::ksortR($arr, $sort_flags);
        }
        return true;
    }

    public static function uniqidReal(int $length = 13)
    {
        if (function_exists('random_bytes')) {
            $bytes = random_bytes(ceil($length / 2));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes(ceil($length / 2));
        } else {
            throw new \Exception('no cryptographically secure random function available');
        }
        return substr(bin2hex($bytes), 0, $length);
    }


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
        Log::error("-----Backtrace:\n".
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

    /**
     * Returns the result of comparing $a to $b, using $cond
     * @param  mixed $a
     * @param  string $cond
     * @param  mixed $b
     * @return bool|null on error
     */
    public static function conditionMet($a, $cond, $b)
    {
        //dump('Util::conditionMet('.json_encode($a).', '.json_encode($cond).', '.json_encode($b).')');
        switch ($cond) {
            case '=':
            case '==':
            case 'eq':
                return $a == $b;

            case '===':
                return $a === $b;

            case '!':
            case '!=':
            case 'not':
                return $a != $b;

            case '!==':
                return $a !== $b;

            case '<':
            case 'lt':
                return $a < $b;

            case '<=':
            case 'lte':
                return $a <= $b;

            case '>':
            case 'gt':
                return $a > $b;

            case '>=':
            case 'gte':
                return $a >= $b;
        }
        Log::error('Unknown condition: '. $cond);
        return null;
    }
}
