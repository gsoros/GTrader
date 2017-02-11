<?php

Namespace GTrader;

use GTrader\Exchange;

abstract class Exchange extends Skeleton
{

    abstract public function getTicker(array $params = []);
    abstract public function getCandles(array $params = []);

    public static function getDefaultExchange()
    {
        $exchange = Exchange::getInstance();
        return $exchange->getParam('local_name');
    }

    public static function getDefaultSymbol()
    {
        $exchange = Exchange::getInstance();
        // reset() returns the first element
        $symbols = $exchange->getParam('symbols');
        $first_symbol = reset($symbols);
        return $first_symbol['local_name'];
    }

    public static function getDefaultResolution()
    {
        $exchange = Exchange::getInstance();
        // reset() returns the first element
        $symbols = $exchange->getParam('symbols');
        $first_symbol = reset($symbols);
        $resolutions = $first_symbol['resolutions'];
        return reset($resolutions);
    }
}
