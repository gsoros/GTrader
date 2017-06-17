<?php

namespace GTrader\Indicators;

use GTrader\Indicators\HasBase;
use GTrader\Series;

if (!extension_loaded('trader')) {
    throw new \Exception('Trader extension not loaded');
}

/** Indicators using the Trader PHP extension */
abstract class Trader extends HasBase
{

    public function __construct(array $params = [])
    {
        trader_set_unstable_period(TRADER_FUNC_UNST_ALL, 0);
        parent::__construct($params);
    }

    public function calculate(bool $force_rerun = false)
    {
        if (!($candles = $this->getCandles())) {
            error_log('Trader::calculate() could not getCandles()');
            return $this;
        }

        $this->runDependencies($force_rerun);

        $values = $this->traderCalc($this->extract($candles));

        $sig = $this->getSignature();

        foreach ($this->getParam('outputs', []) as $output_index => $output_name) {
            $name = $sig;
            if (strlen($output_name)) {
                $name .= '_'.$output_name;
            }
            $candles->setValues(
                $name,
                $values[$output_index],
                $this->getParam('fill_value', null)
            );
        }

        return $this;
    }

    public function extract(Series $candles)
    {
        return $candles->extract($this->getBase());
    }

    abstract function traderCalc(array $values);
}
