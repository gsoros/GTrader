<?php

namespace GTrader\Exchanges\CCXT;

class bitfinex2 extends Supported
{
    public function __construct(array $params = [])
    {
        $params['ccxt_id'] = 'bitfinex2';
        parent::__construct($params);
    }
}
