<?php

namespace GTrader\Indicators;

trait HasStrategy
{
    use \GTrader\HasStrategy;

    public function getStrategyOwner()
    {
        if (!$owner = $this->getOwner()) {
            error_log($this->getShortClass().'::getStrategyOwner() could not get owner');
            return null;
        }
        return $owner;
    }

    public function getDisplaySignature(string $format = 'long', string $output = null)
    {
        $s = $this->getParam('display.name');
        if ('short' === $format) {
            return $s;
        }
        if ($strategy = $this->getStrategy()) {
            if ($n = $strategy->getParam('name')) {
                if ($p = $this->getParamString()) {
                    $n .= ', '.$p;
                }
                return $s.' ('.$n.')';
            }
        }
        return parent::getDisplaySignature($format, $output);
    }
}
