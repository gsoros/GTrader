<?php

namespace GTrader;

trait HasCandles
{
    use Visualizable {
        visualize as __Visualizable__visualize;
    }

    protected $candles;


    public function __clone()
    {
        //dump('HasCandles::__clone() called on '.$this->oid());
        $candles = clone $this->getCandles();
        $this->setCandles($candles);
    }


    public function kill()
    {
        //Log::debug('.');
        if ($this->getCandles()) {
            $this->unsetCandles();
        }
        return $this;
    }


    public function setCandles(Series $candles)
    {
        //dump($this->oid().' setCandles('.$candles->oid().')');
        $this->candles = $candles;
        return $this;
    }


    public function getCandles()
    {
        if (!is_object($this->candles)) {
            //throw new \Exception('No candles');
            //return null;

            $candles = new Series();
            $this->setCandles($candles);
        }
        return $this->candles;
    }


    public function unsetCandles()
    {
        $this->candles = null;
        return $this;
    }


    public function visualize(int $depth = 100)
    {
        $this->__Visualizable__visualize($depth);
        if (!$depth--) {
            return $this;
        }
        if ($node = $this->getCandles()) {
            if (!$this->visNodeExists($node)) {
                if (method_exists($node, 'visualize')) {
                    $node->visualize($depth);
                }
            }
            $this->visAddEdge($this, $node, [
                'title' => $this->getShortClass().' has candles '.$node->getShortClass(),
                'arrows' => '',
                'color' => '#161717',
                'value' => 10,
                'dashes' => true,
            ]);
        }

        return $this;
    }
}
