<?php

namespace GTrader\Exchanges\CCXT;

use GTrader\Log;
use GTrader\HasPCache;

trait HasCCXT
{
    use HasPCache;

    protected static $CCXT_NAMESPACE      = '\\ccxt\\';
    protected static $LOAD_MARKETS_BEFORE = ['markets', 'symbols'];

    protected $ccxt;


    protected function ccxt(string $ccxt_id = '', bool $temporary = false)
    {
        $ccxt_id = strlen($ccxt_id) ? $ccxt_id : $this->getParam('ccxt_id');
        $ccxt_id = strlen($ccxt_id) ? $ccxt_id : null;

        $make_ccxt = function (string $ccxt_id) {
            if (!$ccxt_id) {
                throw new \Exception('Tried to make ccxt without ccxt_id');
                return null;
            }
            $class = self::$CCXT_NAMESPACE.$ccxt_id;
            if (!class_exists($class)) {
                throw new \Exception($class.' does not exist');
                return null;
            }
            return new $class([
                'enableRateLimit' => true,
            ]);
        };

        if ($temporary) {
            if (is_object($this->ccxt) && $this->ccxt->id == $ccxt_id) {
                return $this->ccxt;
            }
            return $make_ccxt($ccxt_id);
        }
        if (!is_object($this->ccxt) ||
            (
                $ccxt_id &&
                is_object($this->ccxt) &&
                $this->ccxt->id !== $ccxt_id
            )) {

            if ($ccxt_id && is_object($this->ccxt) && $this->ccxt->id !== $ccxt_id) {
                Log::debug('a different ccxt exists', $this->oid(), $this->ccxt->id, $ccxt_id);
            }

            if ($ccxt_id) {
                if (is_object($this->ccxt)) {
                    $this->cleanCache();
                }
                $this->ccxt = $make_ccxt($ccxt_id);
            }
        }
        return $this->ccxt;
    }


    public function getCCXTId(): string
    {
        if (!is_object($this->ccxt())) {
            throw new \Exception('ccxt not an object');
        }
        if (!strlen($this->ccxt()->id)) {
            throw new \Exception('ccxt id is empty');
        }
        return $this->ccxt()->id;
    }


    protected function getCCXTpCacheKey($prop): string
    {
        return 'CCXT_'.$this->getParam('ccxt_id').'_'.$this->getCCXTCacheKey($prop);
    }


    protected function getCCXTCacheKey($prop)
    {
        $key = null;
        if (is_array($prop)) {
            if (!count($prop)) {
                Log::error('empty array');
                return $key;
            }
            $key = join('_', $prop);
        } else {
            $key = strval($prop);
        }
        if (!strlen($key)) {
            Log::error('empty str from', $prop);
        }
        return $key;
    }


    protected function &getCCXTTargetProp($prop)
    {
        $nil = null;
        $target = null;
        if (!is_object($ccxt = $this->ccxt())) {
            Log::debug('ccxt not obj, wanted ', $prop);
            return $nil;
        }
        if (is_array($prop)) {
            $key = array_shift($prop);
            if (!isset($ccxt->$key)) {
                Log::debug('Property does not exist', $key, $prop);
                return $nil;
            }
            $target = &$ccxt->$key;
            foreach($prop as $key) {
                if (!isset($target[$key])) {
                    Log::debug('Key does not exist', $key, $prop);
                    return $nil;
                }
                $target = &$target[$key];
            }
        } else {
            if (!isset($ccxt->$prop)) {
                Log::debug('Property does not exist', $prop);
            }
            $target = &$ccxt->$prop;
        }
        return $target;
    }


    public function getCCXTProperty($prop, array $options = [])
    {
        if (!$cache_key = $this->getCCXTCacheKey($prop)) {
            Log::error('could not get cache key for', $prop);
            return null;
        }
        if ($val = $this->cached($cache_key)) {
            return $val;
        }
        $pcache_key = $ccxt = null;
        if (in_array($cache_key, self::$LOAD_MARKETS_BEFORE)) {
            $pcache_key = $this->getCCXTpCacheKey($cache_key);
            if ($val = $this->pCached($pcache_key)) {
                $this->cache($cache_key, $val);
                return $val;
            }
            if (!is_object($ccxt = $this->ccxt())) {
                Log::debug('ccxt not obj, wanted ', $prop);
                return null;
            }
            try {
                Log::debug('loadMarkets() for '.$this->getParam('ccxt_id'), $prop);
                $ccxt->loadMarkets();
            } catch (\Exception $e) {
                Log::debug('loadMarkets() failed for '.$this->getParam('ccxt_id'), $e->getMessage());
                $this->lastError($e->getMessage());
                Log::debug('checking pcache without age');
                if ($val = $this->pCached($pcache_key, null, -1)) {
                    Log::debug('pcache had an older entry');
                    $this->cache($cache_key, $val);
                    return $val;
                }
            }
        }
        $target = $this->getCCXTTargetProp($prop);
        $this->cache($cache_key, $target);
        if ($pcache_key) {
            $this->pCache($pcache_key, $target);
        }
        return $target;
    }


    public function setCCXTProperty($prop, $value, array $options = []): bool
    {
        if (!$cache_key = $this->getCCXTCacheKey($prop)) {
            Log::error('could not get cache key for', $prop);
            return false;
        }
        $ccxt = null;
        if (in_array($cache_key, self::$LOAD_MARKETS_BEFORE)) {
            if (!is_object($ccxt = $this->ccxt())) {
                Log::debug('ccxt not obj, wanted ', $prop);
                return false;
            }
            try {
                Log::debug('loadMarkets() for '.$this->getParam('ccxt_id'));
                $ccxt->loadMarkets();
            } catch (\Exception $e) {
                Log::debug('loadMarkets() failed for '.$this->getParam('ccxt_id'), $e->getMessage());
                $this->lastError($e->getMessage());
            }
        }
        $target = &$this->getCCXTTargetProp($prop);
        //Log::debug('old', $target, 'new', $value);
        $target = $value;
        // TODO unCache and unPCache recursively
        $this->unCache($cache_key);
        return true;
    }
}
