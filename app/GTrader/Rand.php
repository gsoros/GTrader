<?php

namespace GTrader;

class Rand
{
    public static function uniqId(int $length = 13)
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


    public static function int(int $min = 0, int $max = 1): int
    {
        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }
        return mt_rand($min, $max);
    }


    public static function float(float $min = 0, float $max = 1): float
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
    public static function weightedFloat(
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
            return static::float($min, $max);
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
            return static::float($min, $max);
        }
        return $rnd;

        /**
         * Exponential distribution
         */
        // $exp = pow(static::float(), 1 + $weight);
        // if ($peak < static::float($min, $max)) {
        //     return $peak + ($max - $peak) * $exp;
        // }
        // return $min + ($peak - $min) * (1 - $exp);
    }
}
