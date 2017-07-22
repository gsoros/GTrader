<?php

namespace GTrader;

trait HasCandles
{
    protected $candles;


    public function setCandles(Series $candles)
    {
        //dump($this->debugObjId().' setCandles('.$candles->debugObjId().')');
        $this->candles = $candles;
        return $this;
    }


    public function getCandles()
    {
        if (!is_object($this->candles)) {
            $candles = new Series();
            $this->setCandles($candles);
        }
        return $this->candles;
    }


    public function unsetCandles()
    {
        unset($this->candles);
        return $this;
    }
}
