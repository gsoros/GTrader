<?php

namespace GTrader;

abstract class Strategy extends Skeleton
{
    use HasCandles, HasIndicators;


    public function toHTML(string $content = null)
    {
        return view('Strategy', ['strategy' => $this, 'content' => $content]);
    }


    public function getSignalsIndicator()
    {
        $class = $this->getParam('signals_indicator_class');

        foreach ($this->getIndicators() as $indicator)
            if ($class === $indicator->getShortClass())
                return $indicator;

        $indicator = Indicator::make($class, ['display' => ['visible' => false]]);
        $this->addIndicator($indicator);

        return $indicator;
    }

}
