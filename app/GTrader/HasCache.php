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
        Arr::set($this->cache, $key, $value);
        $this->logCache('put', $key, $value);
        return $this;
    }


    public function unCache(string $key)
    {
        Arr::forget($this->cache, $key);
        $this->logCache('forget', $key);
        return $this;
    }


    public function cleanCache()
    {
        $this->cache = [];
        $this->logCache('clean');
        return $this;
    }


    protected function logCache(string $action = null, string $key = null, $value = null)
    {
        if (!$actions = $this->getParam('cache.log')) {
            return $this;
        }

        $action = 'get' === $action ? (is_null($value) ? 'miss' : 'hit') : $action;

        if (!in_array($action, $actions = array_map('trim', explode(',', $actions))) &&
            !in_array('all', $actions)) {
            return $this;
        }

        Log::info(
            'Cache '.$action.': '.$this->debugObjId().' '.json_encode($key).' '.(
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
