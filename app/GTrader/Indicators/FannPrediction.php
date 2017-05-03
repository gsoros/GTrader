<?php

namespace GTrader\Indicators;

use GTrader\Indicator;
use GTrader\Series;

class FannPrediction extends Indicator
{
    protected $allowed_owners = ['GTrader\\Strategies\\Fann'];


    public function calculate(bool $force_rerun = false)
    {
        $signature = $this->getSignature();

        $owner = $this->getOwner();
        $num_samples = $owner->getNumSamples();
        $use_volume = $owner->getParam('use_volume');

        $prediction = [];

        $owner->resetSample();
        while ($sample = $owner->nextSample($num_samples)) {
            if ($use_volume) {
                $volumes = [];
                for ($i = 0; $i < $num_samples - 1; $i++) {
                    $volumes[] = intval($sample[$i]->volume);
                }
            }
            $input = [];
            for ($i = 0; $i < $num_samples; $i++) {
                if ($i < $num_samples - 1) {
                    $input[] = floatval($sample[$i]->open);
                    $input[] = floatval($sample[$i]->high);
                    $input[] = floatval($sample[$i]->low);
                    $input[] = floatval($sample[$i]->close);
                    continue;
                }
                // we only care about the open price for the last candle in the sample
                $input[] = floatval($sample[$i]->open);
            }

            if ($use_volume) {
                // Normalize volumes to -1, 1
                $min = min($volumes);
                $max = max($volumes);
                foreach ($volumes as $k => $v) {
                    $volumes[$k] = Series::normalize($v, $min, $max);
                }
            }

            // Normalize input to -1, 1
            $min = min($input);
            $max = max($input);
            foreach ($input as $k => $v) {
                $input[$k] = Series::normalize($v, $min, $max);
            }

            if ($use_volume) {
                $input = array_merge($volumes, $input);
            }

            $pred = $owner->run($input);
            $prediction[$sample[count($sample)-1]->time] = $pred;
        }

        $candles = $this->getCandles();
        $candles->reset();
        while ($candle = $candles->next()) {
            $val = isset($prediction[$candle->time]) ? $prediction[$candle->time] : 0;
            //error_log('P:'.$val);
            //$price = series::ohlc4($candle);
            $price = $candle->open;
            $candle->$signature = $price + $price * $val * $owner->getParam('output_scaling') / 100;
            //$candles->set($candle);
        }
        return $this;
    }
}
