<?php

namespace GTrader\Indicators;

use GTrader\Indicator;
use GTrader\Log;

class Sampler extends Indicator
{
    use HasStrategy;

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->setAllowedOwners(['GTrader\\Series']);
        //Log::debug($this->getSignature());
    }

    public function getStrategyOwner()
    {
        return $this->getOwner();
    }

    public function calculate(bool $force_rerun = false)
    {
        $this->beforeCalculate();
        return $this;
    }

    public function callback()
    {
    }
}
