<?php

namespace GTrader\Indicators;

use GTrader\Indicator;

class Constant extends Indicator
{
    public function getNormalizeParams()
    {
        return array_replace_recursive(
            parent::getNormalizeParams(),
            [
                'type' => 'individual',
                'to' => $this->getParam('indicator.value'),
            ]
        );
    }

    public function getDisplaySignature(string $format = 'long')
    {
        $name = $this->getParam('indicator.name', 'Constant');
        $value = $this->getParam('indicator.value', 0);
        return ('short' === $format) ? $value : $name.' ('.$value.')';
    }

    public function calculate(bool $force_rerun = false)
    {
        return $this;
    }
}
