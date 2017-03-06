<?php

Namespace GTrader\Exchanges;

use GTrader\Exchange;

class Dummy extends Exchange
{



    /**
     * Get ticker.
     *
     * @param $params array
     * @return array
     */
    public function getTicker(string $symbol)
    {}


    /**
     * Get candles from the exchange.
     *
     * @param $symbol string
     * @param $resolution int
     * @param $since int
     * @param $size int
     * @return array of GTrader\Candle
     */
    public function getCandles(string $symbol,
                                int $resolution,
                                int $since = 0,
                                int $size = 0)
    {}


    public function takePosition(string $symbol,
                                string $signal,
                                float $price,
                                int $bot_id = null)
    {}

    public function cancelUnfilledOrders(string $symbol, int $before_timestamp)
    {}

    public function saveFilledOrders(string $symbol, int $bot_id = null)
    {}


}
