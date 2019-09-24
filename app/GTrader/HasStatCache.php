<?php

namespace GTrader;

use Illuminate\Support\Arr;

trait HasStatCache
{
    protected static $stat_cache = [];
    protected static $stat_cache_max_size = 100;

    public static function statCached(string $key, $default = null)
    {
        $value = Arr::get(static::$stat_cache, $key, $default);
        static::logStatCache('get', $key, $value);
        return $value;
    }


    public static function statCache(string $key, $value = null)
    {
        if (0 < static::$stat_cache_max_size) {
            if (count(static::$stat_cache) >= static::$stat_cache_max_size) {
                static::logStatCache('full', static::$stat_cache_max_size);
                array_shift(static::$stat_cache);
            }
        }
        Arr::set(static::$stat_cache, $key, $value);
        static::logStatCache('put', $key, $value);
    }


    public static function unStatCache(string $key)
    {
        Arr::forget(static::$stat_cache, $key);
        static::logStatCache('forget', $key);
    }


    public static function cleanStatCache()
    {
        static::$stat_cache = [];
        static::logStatCache('clean');
    }


    public static function statCacheSetMaxSize(int $size = 0)
    {
        static::logStatCache('setMaxSize', static::$stat_cache_max_size, $size);
        static::$stat_cache_max_size = $size;
    }


    protected static function logStatCache(string $action = null, string $key = null, $value = null)
    {
        if (!isset(static::$stat_cache_log)) {
            return;
        }
        if (!$actions = static::$stat_cache_log) {
            return;
        }

        $action = 'get' === $action ? (is_null($value) ? 'miss' : 'hit') : $action;

        if (!in_array($action, $actions = array_map('trim', explode(',', $actions))) &&
            !in_array('all', $actions)) {
            return;
        }

        Log::debug(
            'StatCache '.$action.': '.__CLASS__.' '.json_encode($key).' '.(
            is_resource($value) ?
                get_resource_type($value) : (
                    (75 < strlen($j = json_encode($value))) ?
                        substr($j, 0, 71).' ...' : $j
                )
            )
        );
    }
}
