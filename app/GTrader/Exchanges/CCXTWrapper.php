<?php

namespace GTrader\Exchanges;

use GTrader\Exchange;
use GTrader\Trade;
use GTrader\Log;
use ccxt\Exchange as CCXT;

class CCXTWrapper extends Exchange
{


    public function getSupported(): array
    {
        $CCXT = new CCXT;
        Log::debug($CCXT::$exchanges);
        return [$this];
    }

    public function form(array $options = []) {}
    public function getTicker(string $symbol) {}
    public function getCandles(
        string $symbol,
        int $resolution,
        int $since = 0,
        int $size = 0
    ) {}
    public function takePosition(
        string $symbol,
        string $signal,
        float $price,
        int $bot_id = null
    ) {}
    public function cancelUnfilledOrders(string $symbol, int $before_timestamp) {}
    public function saveFilledOrders(string $symbol, int $bot_id = null) {}
}
