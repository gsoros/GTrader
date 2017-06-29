<?php

namespace GTrader;

trait HasStrategy
{
    public $strategy;

    public function getStrategyOwner()
    {
        return $this;
    }

    public function getStrategy()
    {
        if (!$owner = $this->getStrategyOwner()) {
            error_log($this->getShortClass().'::getStrategy() could not get owner');
            return null;
        }
        return $owner->strategy;
    }

    public function setStrategy(Strategy $strategy)
    {
        if (!$owner = $this->getStrategyOwner()) {
            error_log($this->getShortClass().'::setStrategy() could not get owner');
            return null;
        }
        if (is_callable([$owner, 'getCandles']) && !$owner->isClass('GTrader\\Series')) {
            $candles = $owner->getCandles();
            $strategy->setCandles($candles);
        }
        $owner->strategy = $strategy;
        return $this;
    }
}
