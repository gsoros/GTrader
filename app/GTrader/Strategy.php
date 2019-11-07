<?php

namespace GTrader;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

abstract class Strategy extends Base
{
    use HasCandles, HasIndicators, HasCache, HasStatCache, Visualizable
    {
        HasCandles::setCandles as protected __HasCandles__setCandles;
        HasCandles::__clone as protected __HasCandles__clone;
        HasIndicators::__clone as protected __HasIndicators__clone;
        HasCache::__clone as protected __HasCache__clone;
        HasCandles::kill as protected __HasCandles__kill;
        HasIndicators::kill as protected __HasIndicators__kill;
        Visualizable::visualize as __Visualizable__visualize;
        HasIndicators::visualize as __HasIndicators__visualize;
        HasCandles::visualize as __HasCandles__visualize;
    }

    //protected static $stat_cache_log = 'all';


    abstract public function getSignalsIndicator(array $options = []);


    public function kill()
    {
        $this->__HasCandles__kill();
        $this->__HasIndicators__kill();
    }


    public function setCandles(Series $candles)
    {
        $this->__HasCandles__SetCandles($candles);
        $candles->setStrategy($this);
        return $this;
    }


    public static function load(int $id, bool $use_cache = true)
    {
        //Log::debug('Strategy::load', $id);
        if ($use_cache && $strategy = static::statCached('id_'.$id)) {
            //Log::debug('Strategy::load statCached', $id);
            return $strategy;
        }
        $query = DB::table('strategies')
            ->select('user_id', 'name', 'strategy')
            ->where('id', $id)
            ->first();
        if (!is_object($query)) {
            return null;
        }
        $strategy = unserialize($query->strategy);
        $strategy->setParam('id', $id);
        $strategy->setParam('user_id', $query->user_id);
        $strategy->setParam('name', $query->name);
        static::statCache('id_'.$id, $strategy);
        return $strategy;
    }


    public function __sleep()
    {
        return ['params', 'indicators'];
    }


    public function __wakeup()
    {
        $this->cleanCache();
    }


    public function __clone()
    {
        $this->__HasCache__clone();
        $this->__HasIndicators__clone();
        $this->__HasCandles__clone();
        parent::__clone();
    }


    public function save()
    {
        if (!($id = $this->getParam('id'))) {
            Log::error('tried to save strategy without id');
            return $this;
        }
        if (!($user_id = $this->getParam('user_id'))) {
            Log::error('tried to save strategy without user_id');
            return $this;
        }

        if ('new' === $id) {
            $id = DB::table('strategies')
                ->insertGetId([
                    'user_id' => $user_id,
                    'name' => $this->getParam('name'),
                    'strategy' => serialize($this)
                ]);
            $this->setParam('id', $id);
            return $this;
        }
        $affected = DB::table('strategies')
            ->where('id', $id)
            ->where('user_id', $user_id)
            ->update([
                'name' => $this->getParam('name'),
                'strategy' => serialize($this)
            ]);
        $this->cleanCache();
        return $this;
    }


    public function delete()
    {
        $affected = DB::table('strategies')
            ->where('id', $this->getParam('id'))
            ->delete();
        return $this;
    }


    public function toHTML(string $content = null)
    {
        return view('Strategies.Form', [
            'strategy' => $this,
            'injected' => $content,
        ]);
    }


    public function viewIndicatorsList(Request $request = null, array $options = [])
    {
        $format = $this->formatFromRequest($request);
        $indicators = $this->getIndicatorsFilteredSorted(
            ['display.visible' => true],
            ['display.name']
        );
        return view(
            'Indicators/List',
            [
                'owner' => $this,
                'indicators' => $indicators,
                'available' => $this->getAvailableIndicators(),
                'name' => 'strategy_'.$this->getParam('id'),
                'owner_class' => 'Strategy',
                'owner_id' => $this->getParam('id'),
                'display_outputs' => true,
                'target_element' => 'strategy_indicators_list',
                'format' => $format,
            ]
        );
    }


    public static function getListOfUser(int $user_id, bool $return_array = false)
    {
        $strategies_db = DB::table('strategies')
            ->select('id')
            ->where('user_id', $user_id)
            ->orderBy('name')
            ->get();

        $strategies = [];
        foreach ($strategies_db as $strategy_db) {
            $strategy = self::load($strategy_db->id);
            $strategies[] = $strategy;
        }

        $default = self::singleton();
        return $return_array ?
            $strategies :
            view('Strategies.List', [
                'available' => $default->getParam('available'),
                'default' => config('GTrader.Strategy.default_child'),
                'strategies' => $strategies,
            ]);
    }


    public function listItem()
    {
        return $this->getParam('name');
    }


    public static function getStrategiesOfUser(int $user_id)
    {
        if ($s = static::statCached('strategies_of_user_'.$user_id)) {
            return $s;
        }
        $strategies = DB::table('strategies')
            ->select('id', 'name')
            ->where('user_id', $user_id)
            ->orderBy('name')
            ->get();
        $ret = [];
        foreach ($strategies as $strategy) {
            $ret[$strategy->id] = $strategy->name;
        }
        static::statCache('strategies_of_user_'.$user_id, $ret);
        return $ret;
    }


    public static function getSelectorOptions(
        int $user_id,
        int $selected_strategy = null
    ) {
        return view('Strategies.SelectorOptions', [
            'selected_strategy' => $selected_strategy,
            'strategies' => self::getStrategiesOfUser($user_id),
        ]);
    }


    public function handleSaveRequest(Request $request)
    {
        $this->purgeIndicators();
        foreach (['name', 'description'] as $param) {
            $this->setParam($param, $request->$param);
        }
        return $this;
    }


    public function getBalanceIndicator()
    {
        if (!$candles = $this->getCandles()) {
            Log::error('Could not get candles');
            return null;
        }
        if (!$signals = $this->getSignalsIndicator()) {
            Log::error('Could not get Signals');
            return null;
        }
        return $candles->getOrAddIndicator('Balance', [
            'input_signal' => $signals->getSignature(),
        ]);
    }


    public function getLastBalance(bool $force_rerun = false)
    {
        if (!$b = $this->getBalanceIndicator()) {
            Log::error('Could not get balance ind');
            return 0;
        }
        return $b->getLastValue($force_rerun);
    }


    public function getLastProfitability(bool $force_rerun = false)
    {
        if (!$signals = $this->getSignalsIndicator()) {
            Log::error('Could not get Signals');
            return 0;
        }
        return $this->getIndicatorLastValue('Profitability', [
            'input_signal' => $signals->getSignature(),
        ], $force_rerun);
    }


    public function getSignals(bool $force_rerun = false)
    {
        $sig_ind = $this->getSignalsIndicator();
        $sig_ind->checkAndRun($force_rerun);
        $candles = $this->getCandles();
        $signature_signal = $candles->key($sig_ind->getSignature('signal'));
        $signature_price = $candles->key($sig_ind->getSignature('price'));
        $candles->reset();
        $signals = [];
        while ($candle = $candles->next()) {
            if (isset($candle->$signature_signal) &&
                isset($candle->$signature_price)) {
                $signals[$candle->time] = [
                    'signal' => $candle->$signature_signal,
                    'price' => $candle->$signature_price,
                ];
            }
        }
        return $signals;
    }


    public function getNumSignals(bool $force_rerun = false)
    {
        return count($this->getSignals($force_rerun));
    }


    public function fromScratch()
    {
        Log::debug('.');
        return $this;
    }


    protected function visAddMyNode()
    {
        //dump($this->oid().' Indicator::visAddMyNode');
        return $this->visAddNode($this, [
            'label' => $this->getParam('name'),
            'title' => $this->getParam('description'),
            'value' => 30,
            'group' => 'strategies',
        ]);
    }

    public function visualize(int $depth = 100)
    {
        $this->__Visualizable__visualize($depth);
        if ($depth--) {
            $this->__HasIndicators__visualize($depth);
            $this->__HasCandles__visualize($depth);
        }
        return $this;
    }
}
