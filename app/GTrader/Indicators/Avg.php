<?php

namespace GTrader\Indicators;

use GTrader\Indicator;

/** Average */
class Avg extends Indicator
{

    public function basedOnIndicator()
    {
        $available = $this->getParam('available');
        $base = str_replace('-', '_', $this->getParam('indicator.base'));
        $class = Indicator::getClassFromSignature($base);
        return array_key_exists($class, $available);
    }

    public function getOrAddBaseIndicator()
    {
        if (!($owner = $this->getOwner())) {
            return null;
        }
        $base = str_replace('-', '_', $this->getParam('indicator.base'));
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
        $base = $this->getParam('indicator.base');
        if ('volume' === $base) {
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


    public function getAllowedOwners()
    {
        if (!$this->basedOnIndicator()) {
            return ['GTrader\\Series'];
        }
        return ['GTrader\\Strategy'];
        /*
        if (!($indicator = $this->getOrAddBaseIndicator())) {
            return ['GTrader\\Series'];
        }
        return $indicator->getAllowedOwners();
        */
    }


    public function calculate(bool $force_rerun = false)
    {
        $this->runDependencies($force_rerun);

        $base = str_replace('-', '_', $this->getParam('indicator.base'));

        $signature = $this->getSignature();

        if (!($candles = $this->getCandles())) {
            return $this;
        }

        $total = 0;
        $count = 0;

        $candles->reset();
        while ($candle = $candles->next()) {

            if (!isset($candle->$base)) {
                error_log('Avg::calculate() '.$signature.' candle->'.$base.' is not set');
                break;
            }
            $total += $candle->$base;
            $count ++;

            $candle->$signature = $total / $count;

        }
        return $this;
    }
}
