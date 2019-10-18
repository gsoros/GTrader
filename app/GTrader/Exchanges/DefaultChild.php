<?php

namespace GTrader\Exchanges;

use GTrader\Exchange;

class DefaultChild extends Exchange {
    public function getTicker(string $symbol) {}
    public function fetchCandles(
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
