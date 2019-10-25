<?php

namespace GTrader\Exchanges;

use GTrader\Exchange;

class DefaultExchange extends Exchange {

    public function getTicker(string $symbol)
    {
        $this->methodNotImplemented();
    }


    public function fetchCandles(
        string $symbol,
        int $resolution,
        int $since = 0,
        int $size = 0
    )
    {
        $this->methodNotImplemented();
    }


    public function takePosition(
        string $symbol,
        string $signal,
        float $price,
        int $bot_id = null
    )
    {
        $this->methodNotImplemented();
    }


    public function cancelOpenOrders(string $symbol, int $before_timestamp = 0)
    {
        $this->methodNotImplemented();
    }


    public function saveFilledOrders(string $symbol, int $bot_id = null)
    {
        $this->methodNotImplemented();
    }
}
