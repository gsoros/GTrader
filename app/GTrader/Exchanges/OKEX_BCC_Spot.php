<?php

namespace GTrader\Exchanges;

class OKEX_BCC_Spot extends OKCoin
{
    protected function request(string $method, ...$params)
    {
        config(['okcoin.domain', 'www.okex.com']);
        $args = func_get_args();
        return call_user_func_array(['parent', __FUNCTION__], $args);
    }
}
