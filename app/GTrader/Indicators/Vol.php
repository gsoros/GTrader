<?php

namespace GTrader\Indicators;

use GTrader\Indicator;

class Vol extends HasInputs
{
    public function key(string $output = '')
    {
        return 'volume';
        return $this->getInput();
    }

    public function getDisplaySignature(string $format = 'long')
    {
        // hide params if default input is selected
        return $this->getParam('indicator.input_source') ===
            \Config::get('GTrader.Indicators.'.$this->getShortClass().'.indicator.input_source') ?
            'Volume' : parent::getDisplaySignature($format);
    }

    public function runDependencies(bool $force_rerun = false)
    {
        // ROC is used to display the colors
        if ($roc = $this->getOwner()->getOrAddIndicator('Roc', [
            'indicator' => ['input_source' => 'close'],
            ])) {
            $roc->addRef($this);
        }
        return $this;
    }

    public function calculate(bool $force_rerun = false)
    {
        $this->runDependencies($force_rerun);
        return $this;
    }
}
