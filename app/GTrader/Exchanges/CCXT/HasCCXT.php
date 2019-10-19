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


    public function getCcxtId(): string
    {
        if (!is_object($this->ccxt())) {
            throw new \Exception('ccxt not an object');
        }
        if (!strlen($this->ccxt()->id)) {
            throw new \Exception('ccxt id is empty');
        }
        return $this->ccxt()->id;
    }


    public function getCCXTProperty(string $prop, array $options = [])
    {
        if ($val = $this->cached($prop)) {
            return $val;
        }
        $pcache_key = $ccxt = null;
        if (in_array($prop, self::$LOAD_MARKETS_BEFORE)) {
            $pcache_key = 'CCXT_'.$this->getParam('ccxt_id').'_'.$prop;
            if ($val = $this->pCached($pcache_key)) {
                $this->cache($prop, $val);
                return $val;
            }
            if (!is_object($ccxt = $this->ccxt())) {
                Log::debug('ccxt not obj, wanted ', $prop);
                return null;
            }
            try {
                Log::debug('loadMarkets() for '.$this->getParam('ccxt_id'));
                $ccxt->loadMarkets();
            } catch (\Exception $e) {
                Log::debug('loadMarkets() failed for '.$this->getParam('ccxt_id'), $e->getMessage());
                $this->lastError($e->getMessage());
                Log::debug('checking pcache without age');
                if ($val = $this->pCached($pcache_key, null, -1)) {
                    Log::debug('pcache had an older entry');
                    $this->cache($prop, $val);
                    return $val;
                }
            }
        }
        if (!$ccxt && !is_object($ccxt = $this->ccxt())) {
            Log::debug('ccxt not obj, wanted ', $prop);
            return null;
        }
        if (!isset($ccxt->$prop)) {
            return null;
        }
        $this->cache($prop, $ccxt->$prop);
        if ($pcache_key) {
            $this->pCache($pcache_key, $ccxt->$prop);
        }
        return $ccxt->$prop;
    }

}
