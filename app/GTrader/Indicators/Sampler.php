<?php

namespace GTrader\Indicators;

use GTrader\Indicator;

class Sampler extends Indicator
{
    use HasStrategy;

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->allowed_owners = ['GTrader\\Series'];
        //error_log($this->getSignature());
    }

    public function getStrategyOwner()
    {
        return $this->getOwner();
    }

    public function calculate(bool $force_rerun = false)
    {
        return $this;
    }

    public function callback()
    {
    }
}
