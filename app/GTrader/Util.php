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

    public static function randomInt(int $min = 0, int $max = 1): int
    {
        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }
        return mt_rand($min, $max);
    }

    public static function randomFloat(float $min = 0, float $max = 1): float
    {
        return $min + ($max - $min) * mt_rand() / mt_getrandmax();
    }

    /**
     * Returns a random float from the normal distribution.
     * @param  float $min
     * @param  float $max
     * @param  float $peak       $min <= $peak <= $max
     * @param  float $weight     0 <= $weight <= 1
     * @return float
     */
    public static function randomFloatWeighted(
        float $min = 0,
        float $max = 1,
        float $peak = .5,
        float $weight = .5
    ): float
    {
        if ($min === $max) {
            return $min;
        }
        if ($min > $max) {
            [$min, $max] = [$max, $min];
            $peak = $min + $max - $peak;
        }
        if ($peak < $min) {
            $peak = $min;
        }
        if ($peak > $max) {
            $peak = $max;
        }
        if ($weight < 0) {
            $weight = 0;
        }
        if ($weight > 1) {
            $weight = 1;
        }

        /**
         * Normal distribution
         */
        if (0 == $weight) {
            return static::randomFloat($min, $max);
        }
        if (1 == $weight) {
            return $peak;
        }
        $squared_scale = log(1 / $weight);
        $max_deviations = 4;
        $term = ($max - $min) / $max_deviations;
        $max_tries = 10;
        $tries = 0;
        $found = false;
        do {
            $rnd = $peak + $term * \gburtini\Distributions\Normal::draw(0, $squared_scale);
            $found = $min <= $rnd && $rnd <= $max;
        } while ($tries++ < $max_tries && !$found);
        if (!$found) {
            Log::debug('Not found');
            return static::randomFloat($min, $max);
        }
        return $rnd;

        /**
         * Exponential distribution
         */
        // $exp = pow(static::randomFloat(), 1 + $weight);
        // if ($peak < static::randomFloat($min, $max)) {
        //     return $peak + ($max - $peak) * $exp;
        // }
        // return $min + ($peak - $min) * (1 - $exp);
    }


    public static function logTrace()
    {
        $e = new \Exception;
        Log::debug("-----Backtrace:\n".
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
