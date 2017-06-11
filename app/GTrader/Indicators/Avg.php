<?php

namespace GTrader\Indicators;

use GTrader\Indicators\HasBase;

/** Average */
class Avg extends HasBase
{

    public function getAllowedOwners()
    {
        if (!$this->basedOnIndicator()) {
            return ['GTrader\\Series'];
        }
        return ['GTrader\\Strategy'];
        /*
        if (!($indicator = $this->getOrAddBaseIndicator())) {
            return ['GTrader\\Series'];
        }
        return $indicator->getAllowedOwners();
        */
    }


    public function calculate(bool $force_rerun = false)
    {
        $this->runDependencies($force_rerun);

        $base = $this->getBase();

        $signature = $this->getSignature();

        if (!($candles = $this->getCandles())) {
            return $this;
        }

        $total = 0;
        $count = 0;

        $candles->reset();
        while ($candle = $candles->next()) {

            if (!isset($candle->$base)) {
                error_log('Avg::calculate() '.$signature.' candle->'.$base.' is not set');
                break;
            }
            $total += $candle->$base;
            $count ++;

            $candle->$signature = $total / $count;

        }
        return $this;
    }
}
