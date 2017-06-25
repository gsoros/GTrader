<?php

namespace GTrader;

use Illuminate\Support\Arr;

trait HasCache
{
    protected $cache = [];

    public function cached(string $key, $default = null)
    {
        $value = Arr::get($this->cache, $key, $default);
        $this->logCache('get', $key, $value);
        return $value;
    }


    public function cache(string $key, $value = null)
    {
        $this->logCache('put', $key, $value);
        Arr::set($this->cache, $key, $value);
        return $this;
    }


    public function unCache(string $key)
    {
        $this->logCache('forget', $key);
        Arr::forget($this->cache, $key);
        return $this;
    }


    public function cleanCache()
    {
        $this->logCache('clean');
        $this->cache = [];
        return $this;
    }


    protected function logCache(string $action = null, string $key = null, $value = null)
    {
        return $this;
        error_log('Cache '.(
            ('get' === $action) ? (
                is_null($value) ? 'miss' : 'hit'
                ) : $action
                    ).': '.$this->debugObjId().' '.json_encode($key).' '.(
                        is_resource($value) ?
                            get_resource_type($value) : (
                                (75 < strlen($j = json_encode($value))) ?
                                    substr($j, 0, 71).' ...' : $j
                            )
                    )
        );
        return $this;
    }
}
