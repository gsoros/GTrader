<?php

namespace GTrader\Exchanges\CCXT;

use GTrader\Log;

abstract class Supported extends Wrapper
{
    public function __construct(array $params = [])
    {
        $this->setParam('ccxt_id', $this->getShortClass());
        //Log::debug($this->oid().' __construct()', $this->getParams());
        parent::__construct();
    }
}
