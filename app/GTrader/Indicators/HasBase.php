<?php

namespace GTrader\Indicators;

use GTrader\Indicator;

/** Indicators with base */
abstract class HasBase extends Indicator
{

    public function getBase()
    {
        return str_replace('-', '_', $this->getParam('indicator.base'));
    }

    public function basedOnIndicator()
    {
        $available = $this->getParam('available');
        if (!($base = $this->getBase())) {
            return false;
        }
        $class = Indicator::getClassFromSignature($base);
        return array_key_exists($class, $available);
    }

    public function getOrAddBaseIndicator()
    {
        if (!($owner = $this->getOwner())) {
            return null;
        }
        $base = $this->getBase();
        $owner->addIndicatorBySignature($base);
        if (!($indicator = $owner->getIndicator($base))) {
            error_log(get_class($this).': could not find indicator '.$base.' for '.get_class($owner));
            return null;
        }
        return $indicator;
    }


    public function createDependencies()
    {
        if (!$this->basedOnIndicator()) {
            return $this;
        }
        if (!$this->getOrAddBaseIndicator()) {
            error_log('Could not getOrAdd base indicator for '.get_class($this));
        }
        return $this;
    }

    public function runDependencies(bool $force_rerun = false)
    {
        if ('volume' === $this->getBase()) {
            $this->setParam('display.y_axis_pos', 'right');
            return $this;
        }
        if (!$this->basedOnIndicator()) {
            $this->setParam('display.y_axis_pos', 'left');
            return $this;
        }
        if (!($indicator = $this->getOrAddBaseIndicator())) {
            error_log('Avg::runDependencies() could not getOrAdd base indicator for '.get_class($this));
            return $this;
        }
        $this->setParam('display.y_axis_pos', 'right');
        $indicator->checkAndRun($force_rerun);
        return $this;
    }
}
