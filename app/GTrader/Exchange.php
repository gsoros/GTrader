<?php

Namespace GTrader;

abstract class Exchange extends Skeleton
{

    abstract public function getTicker(array $params = []);
    abstract public function getCandles(array $params = []);


}
