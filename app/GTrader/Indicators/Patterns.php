<?php

namespace GTrader\Indicators;

/** CDL_* family of patterns from TA-Lib */
class Patterns extends Trader
{

    public function __construct(array $params = [])
    {
        parent::__construct($params);

        $f = get_defined_functions();
        $functions = $selected = [];
        $prefix = $this->getParam('trader_func_prefix');
        $prefix_length = strlen($prefix);

        foreach ($f['internal'] as $func) {
            if ($prefix !== substr($func, 0, $prefix_length)) {
                continue;
            }
            $f = new \ReflectionFunction($func);
            $args_opt = $args_req = [];
            foreach ($f->getParameters() as $arg) {
                if ($arg->isOptional()) {
                    $args_opt[]= $arg->getName();
                }
                else {
                    $args_req[] = $arg->getName();
                }
            }
            if (['open', 'high', 'low', 'close'] !== $args_req) {
                //dd($func, $args_req, $args_opt);
                continue;
            }
            $func = substr($func, $prefix_length);
            $functions[$func] = $this->getParam('map.'.$func, $func);
            $selected[] = $func;
        }
        $this->setParam('indicator.use_functions', $selected);
        $this->setParam('adjustable.use_functions.items', $functions);
    }


    public function getDisplaySignature(string $format = 'long')
    {
        $name = $this->getParam('display.name');
        if ('short' === $format) {
            return $name;
        }
        return $name;
    }


    public function runDependencies(bool $force_rerun = false)
    {
        return $this;
    }

    public function traderCalc(array $values)
    {
        //dd($values);
        $prefix = $this->getParam('trader_func_prefix');

        $open = $values[$this->getInput('input_open')];
        $high = $values[$this->getInput('input_high')];
        $low = $values[$this->getInput('input_low')];
        $close = $values[$this->getInput('input_close')];

        //end($open);
        //$signals = array_fill(0, key($open), null);

        $signals = [];

        foreach ($this->getParam('indicator.use_functions', []) as $func) {

            if (!function_exists($prefix.$func)) {
                continue;
            }

            $output = call_user_func_array($prefix.$func, [
                $open,
                $high,
                $low,
                $close,
            ]);

            array_walk($open, function ($v, $k) use (&$signals, $func, $output) {
                if (!isset($signals[$k])) {
                    $signals[$k] = [];
                }
                if (isset($output[$k])) {
                    if ($output[$k]) {
                        $signals[$k]['price'] = $v;
                        $signals[$k]['contents'][$this->getParam('map.'.$func, $func)] = $output[$k] / 100;
                    }
                }
            });

        }
        return [$signals];
    }
}
