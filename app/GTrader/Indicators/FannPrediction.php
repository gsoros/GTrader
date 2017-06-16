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
        $signature = $this->getSignature();

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

        $strategy->resetSample();
        while ($sample = $strategy->nextSample($sample_size)) {

            $input = $strategy->sample2io($sample, true);

            $input = $strategy->normalizeInput($input);

            $pred = $strategy->run($input);
            $prediction[$sample[count($sample)-1]->time] = $pred;
        }

        $candles = $this->getCandles();
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
