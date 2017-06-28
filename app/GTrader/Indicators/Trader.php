<?php

namespace GTrader\Indicators;

use GTrader\Series;

if (!extension_loaded('trader')) {
    throw new \Exception('Trader extension not loaded');
}

/** Indicators using the Trader PHP extension */
abstract class Trader extends HasInputs
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

        $fill = $this->getParam('fill_value', null);
        if (is_string($fill) && $this->hasInputs()) {
            if ($fill = $this->getInput($fill)) {
                //error_log('Trader::calculate() fill is '.$fill.' C:'.json_encode($candles->first()));
                $fill = $candles->first()->{$candles->key($fill)};
            }
        }

        foreach ($this->getParam('outputs', []) as $output_index => $output_name) {
            $name = $sig;
            if (strlen($output_name)) {
                $name .= ':::'.$output_name;
            }
            if (!isset($values[$output_index])) {
                continue;
            }
            //error_log(json_encode($values[$output_index]));
            $candles->setValues(
                $name,
                $values[$output_index],
                $fill
            );
        }

        return $this;
    }

    abstract function traderCalc(array $values);
}
