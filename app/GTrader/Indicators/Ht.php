<?php

namespace GTrader\Indicators;

use Illuminate\Support\Arr;
use GTrader\Series;

/** Hilbert Transform */
class Ht extends Trader
{
    protected $setup_done = false;

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        //$this->setup();
    }

    public function __wakeup()
    {
        parent::__wakeup();
        $this->setup();
    }


    public function setup($force = false)
    {
        if ($this->setup_done && !$force) {
            return $this;
        }
        $this->setup_done = true;

        // get the selected mode
        $mode = $this->getParam('indicator.mode');
        if (!is_array($sel = $this->getParam('modes.'.$mode))) {
            error_log('Ht::init() mode not found: '.$mode);
            return $this;
        }

        // set y-axis-pos according to the selected mode
        if ($ypos = Arr::get($sel, 'display.y-axis')) {
            if (in_array($ypos, array_keys($this->getInputs()))) {
                if ($input = $this->getInput($ypos)) {
                    if ($this->inputFrom(['open', 'high', 'low', 'close'])) {
                        $ypos = 'left';
                    } else if ($this->inputFrom(['volume'])) {
                        $ypos = 'right';
                    }
                }
            }
            $this->setParam('display.y-axis', $ypos);
        }

        // set normalize settings according to the selected mode
        if ($norm = Arr::get($sel, 'normalize')) {
            if (is_string($norm)) {
                if (in_array($norm, array_keys($this->getInputs()))) {
                    $sig = $this->getInput($norm);
                    if (in_array($sig, ['open', 'high', 'low', 'close'])) {
                        $this->setParam('normalize', ['mode' => 'ohlc']);
                    } else if ($owner = $this->getOwner()) {
                        if ($ind = $owner->getOrAddIndicator($sig)) {
                            $this->setParam('normalize', $ind->getParam('normalize'));
                        }
                    }
                }
            } elseif (is_array($norm)) {
                $this->setParam('normalize', $norm);
            }
        }

        $this->setParam('outputs', Arr::get($sel, 'outputs', ['']));

        return $this;
    }


    public function getInputs()
    {
        $this->setup();

        $mode = $this->getParam('indicator.mode');
        $sources = $this->getParam('modes.'.$mode.'.sources', []);
        $active_inputs = [];
        foreach (parent::getInputs() as $input_key => $input_val) {
            if (in_array($input_key, $sources)) {
                $active_inputs[$input_key] = $input_val;
            }
        }
        //dump('HT::getInputs() '.$this->debugObjId(), $active_inputs);
        return $active_inputs;
    }


    public function getDisplaySignature(string $format = 'long', string $output = null)
    {
        $this->setup();

        $name = parent::getDisplaySignature('short');
        if ('short' === $format) {
            return $name;
        }
        $inputs = array_keys($this->getInputs());
        $except = [];
        foreach ($this->getParam('indicator') as $key => $param) {
            if ('input_' === substr($key, 0, 6) && !in_array($key, $inputs)) {
                $except[] = $key;
            }
        }
        return ($param_str = $this->getParamString($except)) ? $name.' ('.$param_str.')' : $name;
    }




    public function traderCalc(array $values)
    {
        //dd($values);
        $this->setup(true);

        $func = 'trader_ht_'.$this->getParam('indicator.mode');
        if (!function_exists($func)) {
            error_log('Ht::traderCalc() function not found: '.$func);
            return [];
        }

        $args = [];
        foreach ($this->getInputs() as $input) {
            $args[] = $values[$input];
        }

        if (!$values = call_user_func_array($func, $args)) {
            error_log('Ht::traderCalc() '.$func.' returned false');
            return [];
        }
        //dd($args, $values);
        //dd($this->getParams());
        return 1 < count($this->getOutputs()) ? $values : [$values];
    }


}
