<?php

namespace GTrader\Indicators;

use GTrader\Log;

class Mvwap extends HasInputs
{

    public function runInputIndicators(bool $force_rerun = false)
    {
        if (! $owner = $this->getOwner()) {
            return $this;
        }
        if ($indicator = $owner->getOrAddIndicator($this->getParam('indicator.input_source'))) {
            $indicator->addRef($this);
            $indicator->checkAndRun();
        }
        return $this;
    }


    public function calculate(bool $force_rerun = false)
    {
        $this->runInputIndicators($force_rerun);

        if (!($candles = $this->getCandles())) {
            return $this;
        }

        $period = $this->getParam('indicator.period');
        $input_key = $candles->key($this->getInput('input_source'));
        $output_key = $candles->key($this->getSignature());

        $totals = $volumes = [];

        $candles->reset();
        while ($candle = $candles->next()) {
            if (is_null($input = $candle->$input_key ?? null)) {
                continue;
            }
            $input = floatval($input);

            array_push($totals, $candle->volume * $input);
            array_push($volumes, $candle->volume);

            if ($period < count($totals)) {
                array_shift($totals);
                array_shift($volumes);
            }

            $candle->$output_key = ($volumes_sum = array_sum($volumes)) ?
                array_sum($totals) / $volumes_sum :
                null;
        }
        //dd($candles[17]);
        //dd($this->getParams());
        return $this;
    }
}
