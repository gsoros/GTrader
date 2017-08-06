<?php

namespace GTrader\Indicators;

use Illuminate\Support\Facades\Auth;
use GTrader\Indicator;
use GTrader\Series;
use GTrader\Strategy;

class FannPrediction extends Indicator
{
    use HasStrategy {
        getStrategy as public hasStrategyGetStrategy;
        getParamString as public hasStrategyGetParamString;
    }

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->setAllowedOwners(['GTrader\\Series']);
    }

    public function getParamString(array $except_keys = [], array $overrides = [])
    {
        if (!$strategy = $this->getStrategy()) {
            $strategy_name = 'Could not load strategy';
        }
        elseif (!$strategy->isClass('GTrader\\Strategies\\Fann')) {
            $strategy_name = 'Select a Fann Strategy';
        } else {
            $strategy_name = $strategy->getParam('name', 'Unknown Strategy');
        }
        $overrides = array_replace_recursive($overrides, ['strategy_id' => $strategy_name]);
        return $this->hasStrategyGetParamString($except_keys, $overrides);
    }

    public function getStrategy()
    {
        $strategy_id = intval($this->getParam('indicator.strategy_id'));
        if (-1 == $strategy_id) {
            return $this->hasStrategyGetStrategy();
        }
        if (!$strategy_id) {
            error_log($this->getShortClass().'::getStrategy() invalid strategy_id: '.json_encode($strategy_id));
            return null;
        }
        if (!$strategy = Strategy::load($strategy_id)) {
            error_log($this->getShortClass().'::getStrategy() could not load strategy '.$strategy_id);
            return null;
        }
        return $strategy;
    }

    public function getForm(array $params = [])
    {
        $key = 'adjustable.strategy_id.options';
        if ($options = $this->getParam($key)) {
            if ($user_id = Auth::id()) {
                $strategies = Strategy::getStrategiesOfUser($user_id);
                foreach (array_keys($strategies) as $strat_id) {
                    if ($strategy = Strategy::load($strat_id)) {
                        if (!$strategy->isClass('GTrader\\Strategies\\Fann')) {
                            unset($strategies[$strat_id]);
                        }
                    }
                }
                $this->setParam($key, array_replace($options, $strategies));
            }
        }
        return parent::getForm($params);
    }

    public function calculate(bool $force_rerun = false)
    {
        if (!$candles = $this->getCandles()) {
            error_log('FannPrediction::calculate() no candles');
            return $this;
        }

        if (!$strategy = $this->getStrategy()) {
            error_log('FannPrediction::calculate() no strategy');
            return $this;
        }
        if (!$strategy->isClass('GTrader\\Strategies\\Fann')) {
            error_log('FannPrediction::calculate() not a fann strategy');
            $candles->setValues($this->getSignature(), [], 'open');
            return $this;
        }

        $key = $candles->key($this->getSignature());

        $sample_size = $strategy->getSampleSize();

        $strategy->setCandles($candles);
        $strategy->runInputIndicators($force_rerun);

        $prediction = [];

        //$dumptime = strtotime('2017-06-11 10:00:00');

        $strategy->resetSample();
        while ($sample = $strategy->nextSample($sample_size)) {
            $input = $strategy->sample2io($sample, true);

            $norm_input = $strategy->normalizeInput($input);

            $pred = $strategy->run($norm_input);
            $prediction[$sample[count($sample)-1]->time] = $pred;

            //if ($dumptime == $sample[count($sample)-1]->time) {
            //    error_log('FannPred calc() input: '.json_encode($input).' pred: '.$pred);
            //}
        }

        $candles->reset();

        while ($candle = $candles->next()) {
            $val = $prediction[$candle->time] ?? 0;
            //error_log('P:'.$val);
            //$price = series::ohlc4($candle);
            $price = $candle->open;
            $candle->$key = $price + $price * $val * $strategy->getParam('output_scaling') / 100;
            //$candles->set($candle);
        }
        return $this;
    }
}
