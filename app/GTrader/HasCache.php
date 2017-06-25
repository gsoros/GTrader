<?php

namespace GTrader;

use Illuminate\Support\Arr;

trait HasCache
{
    protected $cache = [];

    public function cached(string $key, $default = null)
    {
        return Arr::get($this->cache, $key, $default);
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


    public function cleanCache()
    {
        $this->cache = [];
        return $this;
    }
}
