<?php

namespace GTrader;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Strategy
{
    use Skeleton, HasCandles, HasIndicators, HasCache
    {
        HasCandles::setCandles as private __hasCandlesSetCandles;
        HasIndicators::getSourcesAvailable as public __HasIndicatorsGetSourcesAvailable;
    }

    //protected static $stat_cache_log = 'all';

    public function setCandles(Series $candles)
    {
        $this->__hasCandlesSetCandles($candles);
        //$candles->setStrategy($this);
        return $this;
    }



    public static function load(int $id)
    {
        if ($strategy = static::statCached('id_'.$id)) {
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
        foreach ($this->getIndicators() as $indicator) {
            $new_indicator = clone $indicator;
            $this->addIndicator($new_indicator);
        }
    }

    public function save()
    {
        if (!($id = $this->getParam('id'))) {
            error_log('tried to save strategy without id');
            return $this;
        }
        if (!($user_id = $this->getParam('user_id'))) {
            error_log('tried to save strategy without user_id');
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
        return view('StrategyForm', [
                    'strategy' => $this,
                    'child_settings' => $content]);
    }


    public function viewIndicatorsList(Request $request = null)
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
                'available' => $this->getIndicatorsAvailable(),
                'name' => 'strategy_'.$this->getParam('id'),
                'owner_class' => 'Strategy',
                'owner_id' => $this->getParam('id'),
                'display_outputs' => true,
                'target_element' => 'strategy_indicators_list',
                'format' => $format,
            ]
        );
    }


    public static function getListOfUser(int $user_id)
    {
        $strategies_db = DB::table('strategies')
                        ->select('id', 'strategy')
                        ->where('user_id', $user_id)
                        ->orderBy('name')
                        ->get();
        $strategies = [];
        foreach ($strategies_db as $strategy_db) {
            $strategy = unserialize($strategy_db->strategy);
            $strategy->setParam('id', $strategy_db->id);
            $strategies[] = $strategy;
        }

        return view(
            'StrategyList',
            [
                'available' => self::singleton()->getParam('available'),
                'strategies' => $strategies]
        );
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
        return view('StrategySelectorOptions', [
            'selected_strategy' => $selected_strategy,
            'strategies' => self::getStrategiesOfUser($user_id),
        ]);
    }


    public function handleSaveRequest(Request $request)
    {
        $this->purgeIndicators();
        foreach (['name', 'description'] as $param) {
            if (isset($request->$param)) {
                $this->setParam($param, $request->$param);
            }
        }
        return $this;
    }


    public function getSignalsIndicator()
    {
        error_log('Strategy::getSignalsIndicator() not overridden in '.$this->getShortClass());
        return null;
    }

    public function getBalanceIndicator()
    {
        if (!$candles = $this->getCandles()) {
            error_log('Strategy::getBalanceIndicator() could not get candles');
            return null;
        }
        if (!$signals = $this->getSignalsIndicator()) {
            error_log('Strategy::getBalanceIndicator() could not get Signals');
            return null;
        }
        return $candles->getOrAddIndicator('Balance', [
            'input_signal' => $signals->getSignature(),
        ]);
    }

    public function getLastBalance(bool $force_rerun = false)
    {
        if (!$b = $this->getBalanceIndicator()) {
            error_log('Strategy::getLastBalance() could not get balance ind');
            return 0;
        }
        return $b->getLastValue($force_rerun);
    }


    public function getLastProfitability(bool $force_rerun = false)
    {
        if (!$signals = $this->getSignalsIndicator()) {
            error_log('Strategy::getLastProfitability() could not get Signals');
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
        $signature = $candles->key($sig_ind->getSignature());
        $candles->reset();
        $signals = [];
        while ($candle = $candles->next()) {
            if (isset($candle->$signature)) {
                $signals[$candle->time] = $candle->$signature;
            }
        }
        return $signals;
    }


    public function getNumSignals(bool $force_rerun = false)
    {
        return count($this->getSignals($force_rerun));
    }



    public function createIndicator(string $signature)
    {
        $ind = Indicator::make($signature);
        if ($ind->hasInputs()) {
            foreach ($ind->getInputs() as $input) {
                $ind->setParam('indicator.'.$input, 'open');
            }
        }
        return $ind;
    }
}
