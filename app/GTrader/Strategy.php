<?php

namespace GTrader;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use GTrader\Page;


class Strategy
{
    use Skeleton, HasCandles, HasIndicators;


    public static function load(int $id)
    {
        $query = DB::table('strategies')
                    ->select('user_id', 'name', 'strategy')
                    ->where('id', $id)
                    ->first();
        if (!is_object($query))
            return null;
        $strategy = unserialize($query->strategy);
        $strategy->setParam('id', $id);
        $strategy->setParam('user_id', $query->user_id);
        $strategy->setParam('name', $query->name);
        return $strategy;
    }


    public function save()
    {
        if (!($id = $this->getParam('id')))
        {
            error_log('tried to save strategy without id');
            return $this;
        }
        if (!($user_id = $this->getParam('user_id')))
        {
            error_log('tried to save strategy without user_id');
            return $this;
        }

        if ('new' === $id)
        {
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


    public static function getListOfUser(int $user_id)
    {
        $strategies_db = DB::table('strategies')
                        ->select('id', 'strategy')
                        ->where('user_id', $user_id)
                        ->orderBy('name')
                        ->get();
        $strategies = [];
        foreach ($strategies_db as $strategy_db)
        {
            $strategy = unserialize($strategy_db->strategy);
            $strategy->setParam('id', $strategy_db->id);
            $strategies[] = $strategy;
        }

        return view('StrategyList', [
                        'available' => self::singleton()->getParam('available'),
                        'strategies' => $strategies]);
    }


    public function listItem()
    {
        return $this->getParam('name');
    }

    public static function getSelectorOptions(
                                int $user_id,
                                int $selected_strategy = null)
    {
        $strategies = DB::table('strategies')
                        ->select('id', 'name')
                        ->where('user_id', $user_id)
                        ->orderBy('name')
                        ->get();

        return view('StrategySelectorOptions', [
                        'selected_strategy' => $selected_strategy,
                        'strategies' => $strategies]);
    }


    public function handleSaveRequest(Request $request)
    {
        foreach (['name'] as $param)
            if (isset($request->$param))
                $this->setParam($param, $request->$param);
        return $this;
    }


    public function getSignalsIndicator()
    {
        $class = $this->getParam('signals_indicator_class');

        foreach ($this->getIndicators() as $indicator)
            if ($class === $indicator->getShortClass())
                return $indicator;
        error_log('Strategy::getSignalsIndicator() creating invisible '.$class);
        $indicator = Indicator::make($class, ['display' => ['visible' => false]]);
        $this->addIndicator($indicator);

        return $indicator;
    }


    public function getLastBalance(bool $force_rerun = false)
    {
        if (!$this->hasIndicatorClass('Balance'))
            $this->addIndicator('Balance');
        $balance = $this->getFirstIndicatorByClass('Balance');
        $balance->checkAndRun($force_rerun);
        $sig = $balance->getSignature();
        if ($last = $this->getCandles()->last())
            return $last->$sig;
        return 0;
    }

    public function getNumSignals(bool $force_rerun = false)
    {
        $signals = $this->getSignalsIndicator();
        $signals->checkAndRun($force_rerun);
        $sig = $signals->getSignature();
        $candles = $this->getCandles();
        $candles->reset();
        $count = 0;
        while ($candle = $candles->next())
            if (isset($candle->$sig))
                $count++;
        return $count;
    }
}
