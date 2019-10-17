<?php

namespace GTrader;

use Illuminate\Support\Facades\DB;

trait HasPCache
{
    protected $pcache_table = 'pcache';
    protected $pcache_max_size = 1000;
    protected $pcache_default_age = 3600; // seconds


    protected function getPCacheTable()
    {
        return DB::table($this->pcache_table);
    }


    public function pCached(string $key, $default = null, int $age = 0)
    {
        $query = $this->getPCacheTable()
            ->select('cache_value')
            ->where('cache_key', $key);
        if (0 <= $age) {
            $query->where(
                'cache_time',
                '>',
                time() - (0 == $age ? $this->pcache_default_age : $age)
            );
        }
        $value = is_object($first = $query->first()) ? $first->cache_value : null;
        $this->logPCache('get', $key, $value);
        return $value ? unserialize($value) : $default;
    }


    public function pCache(string $key, $value = null)
    {
        $table = $this->getPCacheTable();
        if (0 < $this->pcache_max_size) {
            $total = $table->selectRaw('count(*) as total')->first()->total;
            if ($total >= $this->pcache_max_size) {
                $this->logPCache('full', $this->pcache_max_size);
                $table->orderBy('time', 'desc')->limit(1)->delete();
            }
        }
        if ($table
            //->selectRaw('count(*) as total')
            ->select(DB::raw('count(*) as total'))
            ->where('cache_key', $key)
            ->first()->total) {
            $table->where('cache_key', $key)->update([
                'cache_value' => serialize($value),
                'cache_time' => time(),
            ]);
            // generates duplicate where:
            // update `pcache` set `cache_value` = '...', `cache_time` = '1234' where `cache_key` = 'key' and `cache_key` = 'key' limit 1
        }
        else {
            $table->insert([
                'cache_key' => $key,
                'cache_value' => serialize($value),
                'cache_time' => time(),
            ]);
        }
        $this->logPCache('put', $key, $value);
        return $this;
    }


    public function unPCache(string $key)
    {
        $this->getPCacheTable()->where('cache_key', $key)->delete();
        $this->logPCache('forget', $key);
        return $this;
    }


    public function pCacheSetMaxSize(int $size = 0)
    {
        $this->logPCache('setMaxSize', $this->pcache_max_size, $size);
        $this->pcache_max_size = $size;
        return $this;
    }


    protected function logPCache(string $action = null, string $key = null, $value = null)
    {
        if (!$actions = $this->getParam('pcache.log')) {
            return $this;
        }

        $action = 'get' === $action ? (is_null($value) ? 'miss' : 'hit') : $action;

        if (!in_array($action, $actions = array_map('trim', explode(',', $actions))) &&
            !in_array('all', $actions)) {
            return $this;
        }

        Log::debug(
            'PCache '.$action.': '.json_encode($key).' '.
                (75 < strlen($j = json_encode($value)) ? substr($j, 0, 71).' ...' : $j)
        );
        return $this;
    }
}
