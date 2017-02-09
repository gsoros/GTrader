<?php

namespace GTrader;

abstract class Strategy extends Skeleton
{
    use HasCandles, HasIndicators;

    protected $_signals_indicator_class;
    
    
    public function getSignalsIndicator()
    {
        foreach ($this->_indicators as $indicator)
            if ($this->_signals_indicator_class === $indicator->getShortClass())
                return $indicator;
        $indicator = Indicator::make($this->_signals_indicator_class);
        $this->addIndicator($indicator);
        return $indicator;
    }
    
}
