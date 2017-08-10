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
            Log::error('Could not get owner');
            return null;
        }
        return $owner->strategy;
    }

    public function setStrategy(Strategy $strategy)
    {
        if (!$owner = $this->getStrategyOwner()) {
            Log::error('Could not get owner');
            return null;
        }
        //dump($this->debugObjId().' setStrategy('.$strategy->debugObjId().') owner: '.$owner->debugObjId()); flush();
        /*
        if (is_callable([$owner, 'getCandles']) && !$owner->isClass('GTrader\\Series')) {
            $candles = $owner->getCandles();
            $strategy->setCandles($candles);
        }
        */
        $owner->strategy = $strategy;
        return $this;
    }


    public function unsetStrategy()
    {
        if (!$owner = $this->getStrategyOwner()) {
            Log::error('Could not get owner');
            return null;
        }
        $owner->strategy = null;
        return $this;
    }
}
