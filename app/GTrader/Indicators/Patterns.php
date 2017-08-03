<?php

namespace GTrader\Indicators;

use GTrader\HasCache;

/** CDL_* family of patterns from TA-Lib */
class Patterns extends Trader
{
    use HasCache;

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
                    continue;
                }
                $args_req[] = $arg->getName();
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


    public function getDisplaySignature(string $format = 'long', string $output = null)
    {
        $name = $this->getParam('display.name');
        if ('short' === $format) {
            return $name;
        }
        return $name;
    }


    protected function getAnnotationSig()
    {
        if ($annot_sig = $this->cached('annot_sig')) {
            return $annot_sig;
        }

        $annot_ind = clone $this;
        $annot_ind->setParam('outputs', ['annotation']);
        $annot_sig = $annot_ind->getSignature();
        //error_log('Patterns::getAnnotationSig() annot_sig: '.$annot_sig);

        $this->cache('annot_sig', $annot_sig);
        return $annot_sig;
    }

    public function getOutputArray(
        string $index_type = 'sequential',
        bool $respect_padding = false,
        int $density_cutoff = null
    ) {
        if ('annotation' === $this->getParam('display.mode')) {
            if (!$this->getParam('indicator.show_annotation')) {
                return [];
            }

            return $this->getCandles()->extract(
                $this->getAnnotationSig(),
                $index_type,
                $respect_padding,
                $density_cutoff
            );
        }
        if ('line' === $this->getParam('display.mode')) {
            if (!$this->getParam('indicator.show_line')) {
                return [];
            }
            return parent::getOutputArray($index_type, $respect_padding, $density_cutoff);
        }
        return [];
    }

    public function traderCalc(array $values)
    {
        //dd($values);
        $prefix = $this->getParam('trader_func_prefix');

        $open = $values[$this->getInput('input_open')];
        $high = $values[$this->getInput('input_high')];
        $low = $values[$this->getInput('input_low')];
        $close = $values[$this->getInput('input_close')];

        $annotation = $line = [];

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

            $func_label = $this->getParam('map.'.$func, $func);
            array_walk($open, function ($v, $k) use (&$annotation, &$line, $func, $output, $func_label) {
                if (!isset($line[$k])) {
                    $line[$k] = 0;
                }
                if (!isset($annotation[$k])) {
                    $annotation[$k] = [];
                }
                if (isset($output[$k])) {
                    if ($output[$k]) {
                        $value = $output[$k] / 100;
                        $line[$k] += $value;
                        $annotation[$k][0]['price'] = $v;
                        $annotation[$k][0]['contents'][$func_label] = $value;
                    }
                }
            });
        }

        $this->getCandles()->setValues(
            $this->getAnnotationSig(),
            $annotation,
            null
        );

        return [$line];
    }
}
