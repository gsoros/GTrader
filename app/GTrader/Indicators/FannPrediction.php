<?php

namespace GTrader\Indicators;

use GTrader\Indicator;
use GTrader\Series;

class FannPrediction extends Indicator
{

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->allowed_owners = ['GTrader\\Series'];
    }

    public function calculate(bool $force_rerun = false)
    {
        $candles = $this->getCandles();

        $signature = $candles->key($this->getSignature());

        $strategy = $this->getOwner()->getStrategy();
        if (!$strategy) {
            error_log('FannPrediction::calculate() no strategy');
            return $this;
        }
        if (!$strategy->isClass('GTrader\\Strategies\\Fann')) {
            error_log('FannPrediction::calculate() not fann strategy');
            return $this;
        }

        $strategy->runInputIndicators($force_rerun);

        $sample_size = $strategy->getSampleSize();

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
            $val = isset($prediction[$candle->time]) ? $prediction[$candle->time] : 0;
            //error_log('P:'.$val);
            //$price = series::ohlc4($candle);
            $price = $candle->open;
            $candle->$signature = $price + $price * $val * $strategy->getParam('output_scaling') / 100;
            //$candles->set($candle);
        }
        return $this;
    }
}
