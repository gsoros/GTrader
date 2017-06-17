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

    public function hasBase()
    {
        return true;
    }

    public function basedOn(string $target_base)
    {
        if (!$this->hasBase()) {
            return false;
        }
        if ($target_base === ($base = $this->getBase())) {
            return true;
        }
        $params = ['display' => ['visible' => false]];
        if (!($base_indicator = $this->getOwner()->getOrAddIndicator($base, [], $params))) {
            return false;
        }
        if (!$base_indicator->hasBase()) {
            return false;
        }
        if ($base_indicator->basedOn($target_base)) {
            return true;
        }
        return false;
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
        $params = ['display' => ['visible' => false]];
        if (!($indicator = $owner->getOrAddIndicator($base, [], $params))) {
            error_log(get_class($this).'::getOrAddBaseIndicator() could not find indicator '.
                $base.' for '.get_class($owner));
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
            error_log('HasBase::runDependencies() could not getOrAdd base indicator for '.get_class($this));
            return $this;
        }
        $this->setParam('display.y_axis_pos', 'right');
        $indicator->checkAndRun($force_rerun);
        return $this;
    }

}
