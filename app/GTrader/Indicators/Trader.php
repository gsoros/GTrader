<?php

namespace GTrader\Indicators;

use GTrader\Indicators\HasBase;

if (!extension_loaded('trader')) {
    throw new \Exception('Trader extension not loaded');
}

/** Indicators using the Trader PHP extension */
abstract class Trader extends HasBase
{
    protected $allowed_owners = ['GTrader\\Series'];

    public function __construct()
    {
        trader_set_unstable_period(TRADER_FUNC_UNST_ALL, 0);
        parent::__construct();
    }

    public function calculate(bool $force_rerun = false)
    {
        if (!($candles = $this->getCandles())) {
            return $this;
        }

        $this->runDependencies($force_rerun);

        $values = $this->traderCalc($candles->extract($this->getBase()));

        foreach ($this->getParam('output') as $output_index => $output_name) {
            $name = $this->getSignature();
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


    abstract function traderCalc(array $values);
}
