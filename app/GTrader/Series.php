<?php

namespace GTrader;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class Series extends Collection
{
    use HasParams, HasIndicators, HasStrategy, HasCache, ClassUtils
    {
        HasIndicators::__clone as private __HasIndicators__clone;
        HasCache::__clone as private __HasCache__clone;
        HasIndicators::kill as protected __HasIndicators__kill;
        HasStrategy::kill as protected __HasStrategy__kill;
    }

    protected $_loaded;
    protected $_iter = 0;
    protected $_map = [];

    public function __construct(array $params = [])
    {
        $this->setParams(self::loadConfRecursive(get_class($this)));
        foreach (['exchange', 'symbol', 'resolution'] as $param) {
            if (isset($params[$param])) {
                $this->setParam($param, $params[$param]);
            }
            if (!$this->getParam($param)) {
                $this->setParam($param, Exchange::getDefault($param));
            }
        }

        $this->setParam('limit', intval($params['limit'] ?? 200));
        $this->setParam('start', intval($params['start'] ??  0));
        $this->setParam('end', intval($params['end'] ?? 0));
        $this->setParam('resolution', intval($this->getParam('resolution')));

        $this->subscribeEvents();

        parent::__construct();
    }


    public function __destruct()
    {
        $this->subscribeEvents(false);
        //parent::__destruct();
    }


    public function __sleep()
    {
        return ['params', 'indicators'];
    }


    public function __wakeup()
    {
        $this->subscribeEvents();
    }


    public function __clone()
    {
        $this->__HasCache__clone();
        $this->__HasIndicators__clone();
        $this->reset();
        foreach ($this->items as $k => $v) {
            $this->items[$k] = clone $v;
        }
    }


    public function subscribeEvents(bool $subscribe = true)
    {
        $func = $subscribe ? 'subscribe' : 'unsubscribe';
        Event::$func('indicator.change', [$this, 'handleIndicatorChange']);
        Event::$func('indicator.delete', [$this, 'handleIndicatorDelete']);
        return $this;
    }


    public function kill()
    {
        $this->__HasIndicators__kill();
        $this->__HasStrategy__kill();
        $this->subscribeEvents(false);
        return $this;
    }


    public function handleIndicatorChange($object, $event)
    {
        if ($object->getOwner() !== $this) {
            //Log::debug('Not ours, '.$object->oid().' belongs to '.$object->getOwner()->oid());
            return $this;
        }
        if (!$old_sig = Arr::get($event, 'signature.old')) {
            return $this;
        }
        if (!$new_sig = Arr::get($event, 'signature.new')) {
            return $this;
        }
        if (Indicator::signatureSame($old_sig, $new_sig)) {
            return $this;
        }
        $this->unmap($old_sig);
        return $this;
    }


    public function handleIndicatorDelete($object, $event)
    {
        //Log::debug('Delete event received for '.$object->oid());
        if ($object->getOwner() !== $this) {
            //Log::debug('Not ours: '.$object->oid());
            return $this;
        }
        if (!$sig = Arr::get($event, 'signature')) {
            return $this;
        }
        //$d = Indicator::decodeSignature($sig);
        //Log::debug('calling unmap on '.$d['class'].':'.$d['output']);
        $object->unsetOwner();
        $this->unmap($sig);
        return $this;
    }


    protected function unmap(string $sig)
    {

        $keepers = ['time', 'open', 'high', 'low', 'close', 'volume'];
        if (in_array($sig, $keepers)) {
            return $this;
        }
        if (!isset($this->_map[$sig])) {
            return $this;
        }
        $key = $this->_map[$sig];
        if (in_array($key, $keepers)) {
            return $this;
        }

        $deleted = 0;
        $this->reset();
        while ($candle = $this->next()) {
            if (isset($candle->$key)) {
                unset($candle->$key);
                $deleted++;
            }
        }
        unset($this->_map[$sig]);
        //Log::debug('Deleted '.$deleted);
        return $this;
    }


    public function key(string $sig = null, string $prefix = 'key_')
    {
        if (in_array($sig, ['time', 'open', 'high', 'low', 'close', 'volume'])) {
            return $sig;
        }
        if (isset($this->_map[$sig])) {
            return $this->_map[$sig];
        }
        if (in_array($sig, $this->_map)) {
            return $sig;
        }
        $key = null;
        if ($class = Indicator::getClassFromSignature($sig)) {
            $output = Indicator::getOutputFromSignature($sig);
            if ($i = $this->getOrAddIndicator($sig)) {
                if (method_exists($i, 'key')) {
                    $key = $i->key($output);
                }
            }
        }
        if (!$key) {
            $key = $prefix.Rand::uniqId();
        }
        $this->_map[$sig] = $key;
        //dump($sig.' --> '.$key);
        return $key;
    }


    public function getMap()
    {
        return $this->_map;
    }

    public function getCandles()
    {
        $this->_load();
        return $this;
    }


    public function setCandles(Series $candles)
    {
        //$this->clean();
        $this->items = $candles->items;
        return $this;
    }

    public function byKey($key)
    {
        $this->_load();
        return $this->items[$key] ?? null;
    }


    public function next($advance_iterator = true)
    {
        $this->_load();
        //Log::debug($this->_iter);
        $ret = $this->items[$this->_iter] ?? null;
        if ($advance_iterator) {
            $this->_iter++;
        }
        return $ret;
    }


    public function prev($stepback = 1, $redvance_iterator = false)
    {
        $this->_load();
        $ret = isset($this->items[$this->_iter-$stepback-1]) ?
                  $this->items[$this->_iter-$stepback-1] :
                  null;
        if ($redvance_iterator) {
            $this->_iter -= $stepback+1;
        }
        return $ret;
    }


    //public function last()
    //{
    //$this->_load();
    //return $this->items[$this->size()-1];
    //}

    public function set($candle = null)
    {
        $this->_load();
        if (isset($this->items[$this->_iter-1])) {
            $this->items[$this->_iter-1] = $candle;
            return $this;
        }
        return null;
    }


    public function all()
    {
        $this->_load();
        return $this->items;
    }


    public function size(bool $return_display_size = false)
    {
        $this->_load();
        $count = count($this->items);
        if ($return_display_size &&
            $key = $this->getFirstKeyForDisplay()) {
            return (0 <= $count = $count - $key) ? $count : 0;
        }
        return $count;
    }


    public function add($candle)
    {
        $this->_load();
        $this->items[] = $candle;
        return $this;
    }

    public function reset(bool $reset_to_display_start = false)
    {
        $this->_load();
        $key = 0;
        if ($reset_to_display_start &&
            $first = $this->getFirstKeyForDisplay()) {
            $key = $first;
        }
        $this->_iter = $key;
        return $this;
    }


    public function resetTo(int $time)
    {
        $this->_load();
        $k = 0;
        while ($c = $this->byKey($k)) {
            if ($time <= $c->time) {
                $this->_iter = $k;
                //dump('reset to '.$time.' = '.$k, $this);
                return $this;
            }
            $k++;
        }
        //dump('warning, could not reset to '.$time, $this);
        return $this;
    }

    public function first(callable $callback = null, $default = null)
    {
        $this->_load();
        return parent::first($callback, $default);
    }

    public function firstAfter(int $time)
    {
        $this->resetTo($time);
        return $this->next();
    }

    public function clean()
    {
        $this->items = [];
        $this->_loaded = false;
        $this->_iter = 0;
        $this->_map = [];
        $this->cleanCache();
        //dump('cleaned', $this);
        return $this;
    }

    protected function getStartEndLimit(bool $apply_padding = true)
    {
        $cache_key = 'start_end_limit'.($apply_padding ? '_padded' : '');
        if ($sel = $this->cached($cache_key)) {
            return $sel;
        }

        $padding = intval($this->getParam('left_padding'));

        // Start
        $start = (0 < $start = intval($this->getParam('start'))) ? $start : 0;
        if ($apply_padding && $start) {
            $start -= $padding * intval($this->getParam('resolution'));
        }

        // End
        $end = (0 < $end = intval($this->getParam('end'))) ? $end : 0;

        //Limit
        $limit = (0 < $limit = intval($this->getParam('limit'))) ? $limit : 0;
        if ($apply_padding && $limit) {
            $limit += $padding;
        }

        $sel = [
            $start,
            $end,
            $limit,
        ];
        $this->cache($cache_key, $sel);

        return $sel;
    }


    protected function _load()
    {
        if ($this->_loaded) {
            return $this;
        }
        $this->_loaded = true;

        if (count($this->items)) {
            return $this;
        }

        $this->cleanCache();

        $resolution = intval($this->getParam('resolution'));

        list($start, $end, $limit) = $this->getStartEndLimit(true);

        $qbuilder = DB::table('candles')
            ->select('time', 'open', 'high', 'low', 'close', 'volume')
            ->where('resolution', $resolution)
            ->join('exchanges', 'candles.exchange_id', '=', 'exchanges.id')
            ->where('exchanges.name', $this->getParam('exchange'))
            ->join('symbols', function ($join) {
                $join->on('candles.symbol_id', '=', 'symbols.id')
                    ->whereColumn('symbols.exchange_id', '=', 'exchanges.id');
            })
            ->where('symbols.name', $this->getParam('symbol'))
            ->when($start, function ($query) use ($start) {
                return $query->where('time', '>=', $start);
            })
            ->when($end, function ($query) use ($end) {
                return $query->where('time', '<=', $end);
            })
            ->orderBy('time', 'desc')
            ->when(0 < $limit, function ($query) use ($limit) {
                return $query->limit($limit);
            }
        );

        //Log::debug(\GTrader\DevUtil::eloquentSql($qbuilder));

        $candles = $qbuilder
            ->get()
            ->reverse()
            ->values();

        //if ($candles->isEmpty()) throw new \Exception('Empty result');
        if (! $count = count($candles->items)) {
            return $this->reset();
        }

        /*
                if ($count < intval($this->getParam('left_padding'))) {
                    $this->setParam('left_padding', 0);
                    $this->cleanCache();
                }
        */

        $this->items = $candles->items;
        //dump('SeriesNg::_load()', $this->items);
        //$this->setParam('start', $this->next()->time);
        //$this->setParam('end', $this->last()->time);
        return $this->reset();
    }


    public function getFirstKeyForDisplay()
    {
        if (!$limit = intval($this->getParam('limit'))) {
            return 0;
        }
        $total = $this->count();
        $padding = intval($this->getParam('left_padding'));
        $key = 0 < $total - $padding ? $total - $limit : 0;
        //Log::error('total: '.$total.' padding: '.$padding.' limit: '.$limit.' key: '.$key);
        return $key;
    }


    public function save()
    {
        if ($e = $this->getParam('exchange')) {
            if ($r = Exchange::getOrAddIdByName($e)) {
                $exchange_id = intval($r);
            }
            if ($s = $this->getParam('symbol')) {
                if ($s = Exchange::getSymbolIdByExchangeSymbolName($e, $s)) {
                    $symbol_id = intval($s);
                }
            }
        }
        if ($r = $this->getParam('resolution')) {
            $resolution = intval($r);
        }

        $this->reset();
        while ($candle = $this->next()) {
            if (isset($exchange_id)) {
                $candle->exchange_id = $exchange_id;
            }
            if (isset($symbol_id)) {
                $candle->symbol_id = $symbol_id;
            }
            if (isset($resolution)) {
                $candle->resolution = $resolution;
            }
            self::saveCandle($candle);
        }
        return $this;
    }


    public static function saveCandle($candle)
    {
        $table = config('GTrader.Series.table');
        $attributes = ['id', 'time', 'exchange_id', 'symbol_id', 'resolution',
            'open', 'high', 'low', 'close', 'volume'];

        if (! $vars = get_object_vars($candle)) {
            Log::error('Could not get object vars for', $candle);
            return null;
        }
        foreach ($vars as $k => $v) {
            if (!in_array($k, $attributes)) {
                Log::info('Not saving attribute '.$k.' = '.$v);
                unset($candle->$k);
            }
        }

        $query = DB::table($table)->select('id');

        foreach (['time', 'exchange_id', 'symbol_id', 'resolution'] as $k) {
            if (!isset($candle->$k)) {
                Log::error('Cannot save without '.$k);
                return null;
            }
            $query->where($k, $candle->$k);
        }

        if (is_object($query->first())) {
            DB::table($table)
                ->where('id', $query->first()->id)
                ->update(get_object_vars($candle));
            return null;
        }

        return DB::table($table)->insert(get_object_vars($candle));
    }


    public static function sanitizeCandle($candle)
    {
        if ($candle->high < ($max = max($candle->open, $candle->low, $candle->close))) {
            $candle->high = $max;
        }
        if ($candle->low < ($min = min($candle->open, $candle->high, $candle->close))) {
            $candle->low = $min;
        }
        return $candle;
    }

    public function getEpoch($resolution = null, $symbol = null, $exchange = null)
    {
        foreach ([ 'resolution', 'symbol', 'exchange'] as $param) {
            if (is_null($$param)) {
                $$param = $this->getParam($param);
            }
        }

        $cache_key = $exchange.'-'.$symbol.'-'.$resolution.'.epoch';

        if ($cached = $this->cached($cache_key)) {
            return $cached;
        }

        $candle = DB::table('candles')
            ->select('time')
            ->join('exchanges', 'candles.exchange_id', '=', 'exchanges.id')
            ->where('exchanges.name', $exchange)
            ->join('symbols', function ($join) {
                $join->on('candles.symbol_id', '=', 'symbols.id')
                    ->whereColumn('symbols.exchange_id', '=', 'exchanges.id');
            })
            ->where('symbols.name', $symbol)
            ->where('resolution', $resolution)
            ->orderBy('time')
            ->first();

        $epoch = $candle->time ?? null;
        $this->cache($cache_key, $epoch);

        return $epoch;
    }


    public function getLastInSeries($resolution = null, $symbol = null, $exchange = null)
    {
        foreach ([ 'resolution', 'symbol', 'exchange'] as $param) {
            if (is_null($$param)) {
                $$param = $this->getParam($param);
            }
        }

        $cache_key = $exchange.'-'.$symbol.'-'.$resolution.'.last';

        if ($cached = $this->cached($cache_key)) {
            return $cached;
        }

        $candle = DB::table('candles')
            ->select('time')
            ->join('exchanges', 'candles.exchange_id', '=', 'exchanges.id')
            ->where('exchanges.name', $exchange)
            ->join('symbols', function ($join) {
                $join->on('candles.symbol_id', '=', 'symbols.id')
                    ->whereColumn('symbols.exchange_id', '=', 'exchanges.id');
            })
            ->where('symbols.name', $symbol)
            ->where('resolution', $resolution)
            ->orderBy('time', 'desc')->first();

        $last = $candle->time ?? null;
        $this->cache($cache_key, $last);

        return $last;
    }


    public function extract(
        string $field,
        string $index_type = 'sequential',
        bool $respect_padding = false,
        int $density_cutoff = null
    ) {
        if (! $key = $this->key($field)) {
            Log::error('Got no key for '.$field);
            return [];
        }
        $nth = 1;
        if (1 < $density_cutoff) {
            //$total = $this->count($respect_padding);
            $total = $this->size($respect_padding);
            $nth = 1 < ($nth = floor($total / $density_cutoff)) ? $nth : 1;
            //Log::debug('total: '.$total.' density: '.$density_cutoff.' nth: '.$nth);
        }
        $this->reset($respect_padding);
        $ret = [];
        $curr = 1;
        while ($candle = $this->next()) {
            if ($curr < $nth) {
                $curr++;
                continue;
            }
            $curr = 1;
            if ('time' === $index_type) {
                $ret[intval($candle->time)] = $candle->$key ?? null;
                continue;
            }
            $ret[] = $candle->$key ?? null;
        }
        return $ret;
    }



    public function setValues(string $field, array $values, $fill_value = null)
    {
        $key = $this->key($field);
        if (is_null($fill_value)) {
            // first valid value
            $fill_value = reset($values);
        }

        $i = 0;
        while ($candle = $this->byKey($i)) {
            $fill = $fill_value;
            if (in_array($fill, ['open', 'high', 'low', 'close'], true)) {
                $fill = $candle->$fill;
            }
            $candle->$key = $values[$i] ?? $fill;
            $i++;
        }
        return $this;
    }



    public function realSlice(int $offset, int $length = null, bool $preserve_keys = false)
    {
        $this->_load();
        return array_slice($this->items, $offset, $length, $preserve_keys);
    }






    /** Midrate */
    public static function ohlc4($candle)
    {
        if (isset($candle->open) && isset($candle->high) && isset($candle->low) && isset($candle->close)) {
            return ($candle->open + $candle->high + $candle->low + $candle->close) / 4;
        }
        throw new \Exception('Candle component missing');
    }



    // Series::crossover($prev_candle, $candle, $key, 50)
    // Series::crossunder($prev_candle, $candle, 'close', $key)
    public static function crossover($prev_candle, $candle, $fish, $sea, $direction = 'over')
    {
        if (is_numeric($fish)) {
            $fish1 = $fish2 = $fish + 0;
        } elseif (is_string($fish)) {
            if (isset($prev_candle->$fish)) {
                $fish1 = $prev_candle->$fish;
            } else {
                throw new \Exception('Could not find fish1');
            }
            if (isset($candle->$fish)) {
                $fish2 = $candle->$fish;
            } else {
                throw new \Exception('Could not find fish2');
            }
        } else {
            throw new \Exception('Fish must either be string or numeric');
        }

        if (is_numeric($sea)) {
            $sea1 = $sea2 = $sea+0;
        } elseif (is_string($sea)) {
            if (isset($prev_candle->$sea)) {
                $sea1 = $prev_candle->$sea;
            } else {
                throw new \Exception('Could not find sea1');
            }
            if (isset($candle->$sea)) {
                $sea2 = $candle->$sea;
            } else {
                throw new \Exception('Could not find sea2');
            }
        } else {
            throw new \Exception('Sea must either be string or numeric');
        }

        return $direction == 'under' ?
            $fish1 > $sea1 && $fish2 < $sea2:
            $fish1 < $sea1 && $fish2 > $sea2;
    }


    public static function crossunder($prev_candle, $candle, $fish, $sea)
    {
        return self::crossover($prev_candle, $candle, $fish, $sea, 'under');
    }


    public static function normalize($in, $in_min, $in_max, $out_min = -1, $out_max = 1)
    {
        if (0 == $in_max - $in_min) {
            //Log::error('division by zero: '.$in.' '.$in_min.' '.$in_max.' '.$out_min.' '.$out_max);
            return ($out_min + $out_max) / 2;
        }
        return ($out_max - $out_min) / ($in_max - $in_min) * ($in - $in_max) + $out_max;
    }


    public function debug()
    {
        $map = $this->getMap();
        $map_classes = [];
        array_walk($map, function($k, $sig) use (&$map_classes) {
            $map_classes[$k] = Indicator::decodeSignature($sig)['class'];
        });

        $array_freq = function($a) {
            $count = array_count_values($a);
            $freq = [];
            array_walk(
                $count,
                function($v, $k) use (&$freq) {
                    $freq[] = $k.': '.$v;
                }
            );
            return $freq;
        };

        $map_freq = $array_freq($map_classes);
        $cv = [];
        $this->reset();
        while ($ca = $this->next()) {
            foreach ((array)$ca as $ck => $cf) {
                if (!in_array($ck, $cv)) {
                    $cv[] = $ck;
                }
            }
        }
        foreach ($cv as $k => $v) {
            if (isset($map_classes[$v])) {
                $cv[$k] = $map_classes[$v];
            }
        }

        return $this->oid().
            ' Inds: '.count($this->getIndicators()).
            ', Map: '.count($map).' ('.count(array_unique($map)).
            ' unique: ['.join(', ', $map_freq).
            ']), candle values: '.count($cv).' ('.join(', ', $array_freq($cv)).')';
    }


    protected function visAddMyNode()
    {
        //dump($this->oid().' Indicator::visAddMyNode');
        return $this->visAddNode($this, [
            'value' => 10,
            'group' => 'candles',
        ]);
    }
}
