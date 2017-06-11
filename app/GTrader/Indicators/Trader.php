<?php

namespace GTrader\Indicators;

use GTrader\Indicators\HasBase;

if (!extension_loaded('trader')) {
    throw new \Exception('Trader extension not loaded');
}

/** Indicators using the Trader PHP extension */
abstract class Trader extends HasBase
{
    protected $allowed_owners = ['GTrader\\Series'];

    public function calculate(bool $force_rerun = false)
    {
        if (!($candles = $this->getCandles())) {
            return $this;
        }

        $this->runDependencies($force_rerun);

        $candles->setValues(
            $this->getSignature(),
            $this->traderCalc($candles->extract($this->getBase())),
            $this->getParam('fill_value', null)
        );

        return $this;
    }


    abstract function traderCalc(array $values);
}
