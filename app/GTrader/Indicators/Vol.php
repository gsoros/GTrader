<?php

namespace GTrader\Indicators;

use GTrader\Indicator;

class Vol extends HasInputs
{
    public function init()
    {
        return $this;
    }

    public function key(string $output = '')
    {
        return 'volume';
    }

    public function getDisplaySignature(string $format = 'long', string $output = null)
    {
        // hide params if default input is selected
        return $this->getParam('indicator.input_source') ===
            \Config::get('GTrader.Indicators.'.$this->getShortClass().'.indicator.input_source') ?
            'Volume' : parent::getDisplaySignature($format);
    }

    public function runInputIndicators(bool $force_rerun = false)
    {
        // sub(close, open) is used to display the colors
        if ($op = $this->getOwner()->getOrAddIndicator('Operator', [
            'input_a' => 'close',
            'operation' => 'sub',
            'input_b' => 'open',
        ])) {
            $op->addRef($this);
            $op->checkAndRun();
        }
        return $this;
    }

    public function calculate(bool $force_rerun = false)
    {
        $this->runInputIndicators($force_rerun);
        return $this;
    }
}
