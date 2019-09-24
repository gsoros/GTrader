<?php

namespace GTrader;

trait HasStrategy
{
    public $strategy;


    public function kill()
    {
        $this->unsetStrategy();
        return $this;
    }


    public function getStrategyOwner()
    {
        return $this;
    }

    public function getStrategy()
    {
        if (!$owner = $this->getStrategyOwner()) {
            Log::error('Could not get owner 1');
            return null;
        }
        return $owner->strategy;
    }

    public function setStrategy(Strategy $strategy)
    {
        if (!$owner = $this->getStrategyOwner()) {
            Log::error('Could not get owner 2');
            return $this;
        }
        //dump($this->oid().' setStrategy('.$strategy->oid().') owner: '.$owner->oid()); flush();
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
        if ($owner = $this->getStrategyOwner()) {
            $owner->strategy = null;
        }
        return $this;
    }
}
