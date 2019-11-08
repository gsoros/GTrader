<?php

namespace GTrader\Indicators;

use GTrader\Series;
use GTrader\Log;


/** Indicators using the Trader PHP extension */
abstract class Trader extends HasInputs
{
    public function __construct(array $params = [])
    {
        if (!extension_loaded('trader')) {
            throw new \Exception('Trader extension not loaded');
        }
        trader_set_unstable_period(TRADER_FUNC_UNST_ALL, 0);
        parent::__construct($params);
        $this->unsetParam('MA_TYPES');
    }

    public function calculate(bool $force_rerun = false)
    {
        $this->beforeCalculate();
        
        if (!($candles = $this->getCandles())) {
            Log::error('could not getCandles()');
            return $this;
        }

        $this->runInputIndicators($force_rerun);
        $this->setAutoYAxis();

        $values = $this->traderCalc($this->extract($candles));


        $fill = $this->getParam('fill_value', null);
        if (is_string($fill) && $this->hasInputs()) {
            if ($fill = $this->getInput($fill)) {
                //dump('Trader::calculate() fill is '.$fill.' C:', $candles->first());
                $fill = $candles->first()->{$candles->key($fill)};
            }
        }

        foreach ($this->getOutputs() as $output_index => $output_name) {
            if (!isset($values[$output_index])) {
                continue;
            }
            //Log::error(json_encode($values[$output_index]));
            //dump('Trader::calc() sig: '.$this->getSignature($output_name));
            $candles->setValues(
                $this->getSignature($output_name),
                $values[$output_index],
                $fill
            );
        }

        return $this;
    }

    abstract public function traderCalc(array $values);
}
