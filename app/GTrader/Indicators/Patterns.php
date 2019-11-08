<?php

namespace GTrader\Indicators;

use Illuminate\Support\Arr;
use GTrader\HasCache;
use GTrader\Log;

/** CDL_* family of patterns from TA-Lib */
class Patterns extends Trader
{
    use HasCache;

    public function __construct(array $params = [])
    {
        parent::__construct($params);

        $this->setAvailableFunctions();

        $selected = Arr::get($params, 'indicator.use_functions', []);
        if (!is_array($selected)) {
            $selected = [];
        }
        $this->setSelectedFunctions($selected);
    }


    public function update(array $params = [], string $suffix = '')
    {
        parent::update($params, $suffix);
        $this->setSelectedFunctions($this->getParam('indicator.use_functions', []));
        return $this;
    }


    public function setSelectedFunctions(array $selected = [])
    {
        if (!is_array($available = $this->getParam('adjustable.use_functions.items', []))) {
            $available = [];
        }
        $available = array_keys($available);

        $valid = [];
        foreach ($selected as $func) {
            if (in_array($func, $available)) {
                $valid[] = $func;
            }
        }

        // select all if none are selected
        if (!$valid_count = count($valid)) {
            $valid = $available;
        }
        //Log::debug('selecting '.($valid_count ?? 'all').' of '.count($available).' available funcs');
        $this->setParam('indicator.use_functions', $valid);

        return $this;
    }

    public function setAvailableFunctions()
    {
        if ($this->cached('available_set')) {
            return $this;
        }
        if ($funcs = static::statCached('available_funcs')){
            $this->setParam('adjustable.use_functions.items', $funcs);
            $this->cache('available_set', true);
            return $this;
        }

        //dd(\GTrader\Util::backtrace());
        //dump('Patterns::setAvailableFunctions()');

        $f = get_defined_functions();
        $funcs = [];
        $prefix = $this->getParam('trader_func_prefix');
        $prefix_length = strlen($prefix);

        //Log::debug('setting up available funcs');

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
            $funcs[$func] = $this->getParam('map.'.$func, $func);
        }
        $this->setParam('adjustable.use_functions.items', $funcs);

        static::statCache('available_funcs', $funcs);
        $this->cache('available_set', true);
        return $this;
    }


    public function getDisplaySignature(
      string $format = 'long',
      string $output = null,
      array $overrides = [])
    {
        $name = $this->getParam('display.name');
        if ('short' == $format) {
            return $name;
        }
        $selected = count($this->getParam('indicator.use_functions', []));
        return $name.' ('.
            ($selected == count($this->getParam('adjustable.use_functions.items', [])) ?
            'all' : $selected).')';
    }


    protected function getAnnotationSig()
    {
        if ($annot_sig = $this->cached('annot_sig')) {
            return $annot_sig;
        }

        $annot_ind = clone $this;
        $annot_ind->setParam('outputs', ['annotation']);
        $annot_sig = $annot_ind->getSignature();
        //Log::debug('annot_sig: '.$annot_sig);

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
            array_walk($open, function ($v, $k) use (&$annotation, &$line, $output, $func_label) {
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

        if ($this->getParam('indicator.show_annotation')) {
            $this->getCandles()->setValues(
                $this->getAnnotationSig(),
                $annotation,
                null
            );
        }

        return [$line];
    }
}
