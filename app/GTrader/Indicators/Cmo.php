<?php

namespace GTrader\Indicators;

use GTrader\Indicators\Trader;

class Cmo extends Trader
{

    public function runDependencies(bool $force_rerun = false)
    {
       if (! $inds = $this->getOrAddInputIndicators()) {
            return $this;
        }
        foreach ($inds as $ind) {
            $ind->addRef($this->getSignature());
            $ind->checkAndRun($force_rerun);
        }
        return $this;
    }

    public function traderCalc(array $values)
    {
        if (!($values = trader_cmo(
            $values[$this->getInput('input_source')],
            $this->getParam('indicator.period')
            ))) {
            error_log('trader_cmo returned false');
            return [];
        }
        return [$values];
    }
}
