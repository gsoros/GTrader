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
    public function getTicker(array $params = [])
    {
    }


    /**
     * Get candles.
     *
     * @param $params array
     * @return array
     */
    public function getCandles(array $params = [])
    {
    }


    public function takePosition(string $position)
    {
    }



}
