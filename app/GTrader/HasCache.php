<?php

namespace GTrader;

use Illuminate\Support\Arr;

trait HasCache
{
    protected $cache = [];
    protected $log_cache_hits = false;

    public function cached(string $key, $default = null)
    {
        $value = Arr::get($this->cache, $key, $default);

        if ($this->log_cache_hits && $value) {
            error_log('Cache hit: '.$this->debugObjId().' '.
                json_encode($key).': '.
                (is_resource($value) ? get_resource_type($value) : json_encode($value)));
        }

        return $value;
    }


    public function cache(string $key, $value = null)
    {
        Arr::set($this->cache, $key, $value);
        return $this;
    }


    public function unCache(string $key)
    {
        Arr::forget($this->cache, $key);
        return $this;
    }
}
