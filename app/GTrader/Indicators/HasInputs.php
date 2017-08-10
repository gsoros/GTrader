<?php

namespace GTrader\Indicators;

use Illuminate\Support\Arr;

use GTrader\Series;
use GTrader\Indicator;
use GTrader\Event;
use GTrader\Log;

abstract class HasInputs extends Indicator
{

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->subscribeEvents();
    }


    public function __wakeup()
    {
        parent::__wakeup();
        $this->subscribeEvents();
    }


    protected function subscribeEvents()
    {
        Event::subscribe('indicator.change', [$this, 'handleIndicatorChange']);
        return $this;
    }


    public function init()
    {
        if (!$owner = $this->getOwner()) {
            return $this;
        }
        foreach ($this->getInputs() as $input_key => $input_val) {
            if (in_array($input_val, ['open', 'high', 'low', 'close', 'volume'])) {
                $this->setParam(
                    'indicator.'.$input_key,
                    $owner->getFirstIndicatorOutput($input_val)
                );
            }
        }
        return parent::init();
    }


    public function handleIndicatorChange($object, $event)
    {
        if ($object === $this) {
            return true;
        }
        if (!$old_sig = Arr::get($event, 'signature.old')) {
            return true;
        }
        if (!$new_sig = Arr::get($event, 'signature.new')) {
            return true;
        }
        if (Indicator::signatureSame($old_sig, $new_sig)) {
            return true;
        }
        $changed = null;
        foreach ($this->getInputs() as $input_key => $input_sig) {
            if (!$owner = $this->getOwner()) {
                return true;
            }
            if (Indicator::signatureSame($input_sig, $old_sig)) {
                if (is_null($changed)) {
                    $changed = $this->getSignature();
                }
                $output = Indicator::getOutputFromSignature($input_sig);
                $ind = $owner->getOrAddIndicator($new_sig);
                $this->setParam('indicator.'.$input_key, $ind->getSignature($output));
            }
        }
        if (!is_null($changed)
            && ($changed !== ($after = $this->getSignature()))) {
            Event::dispatch(
                $this,
                'indicator.change',
                [
                    'signature' => [
                        'old' => $changed,
                        'new' => $after,
                    ],
                ]
            );
        }
        return true;
    }


    public function getInputs()
    {
        $inputs = [];
        if (!$this->hasinputs()) {
            return $inputs;
        }
        //Log::debug('Params: '.json_encode($this->getParam('indicator')));
        foreach ($this->getParam('indicator', []) as $k => $v) {
            if ('input_' === substr($k, 0, 6)) {
                if (!is_string($v)) {
                    $v = json_encode($v);
                    //dd('HasInputs::getInputs() val is not a str: '.$v, debug_backtrace());
                }
                $inputs[$k] = $v;
            }
        }
        //dump('HasInputs::getInputs() '.$this->debugObjId(), $inputs);
        return $inputs;
    }


    public function getInput(string $name = null)
    {
        if (!$this->hasinputs()) {
            return null;
        }
        if (!is_null($name)) {
            $name = $this->getParam('indicator.'.$name);
            if (!is_string($name)) {
                $name = json_encode($name);
            }
            return $name;
        }
        $inputs = $this->getInputs();
        return array_shift($inputs);
    }


    public function hasInputs()
    {
        return true;
    }


    public function inputFrom($signatures)
    {
        if (!$this->hasInputs()) {
            return false;
        }
        if (!is_array($signatures)) {
            $signatures = [$signatures];
        }
        $inputs = $this->getInputs();
        if (count(array_intersect($inputs, $signatures))) {
            return true;
        }
        if (!$owner = $this->getOwner()) {
            Log::error('Could not get owner of '.$this->getShortClass());
            return false;
        }
        foreach ($inputs as $input) {
            if (!($input_ind = $owner->getOrAddIndicator($input))) {
                continue;
            }
            if (!$input_ind->hasInputs()) {
                continue;
            }
            if ($input_ind->inputFrom($signatures)) {
                return true;
            }
        }
        return false;
    }


    public function inputFromIndicator()
    {
        $available = $this->getOwner()->getIndicatorsAvailable();
        foreach ($this->getInputs() as $input) {
            $class = Indicator::getClassFromSignature($input);
            if (array_key_exists($class, $available)) {
                return true;
            }
        }
        return false;
    }


    public function getOrAddInputIndicators()
    {
        if (!($owner = $this->getOwner())) {
            return null;
        }
        $inputs = $this->getInputs();
        $inds = [];
        foreach ($inputs as $input) {
            if (!($indicator = $owner->getOrAddIndicator($input))) {
                //Log::error('Could not find indicator '.
                //    $input.' for '.get_class($owner));
                continue;
            }
            $inds[] = $indicator;
            $indicator->addRef($this);
        }
        return count($inds) ? $inds : null;
    }



    public function createDependencies()
    {
        if (!$this->inputFromIndicator()) {
            return $this;
        }
        if (!$this->getOrAddInputIndicators()) {
            //Log::error('Could not getOrAdd input indicators for '.get_class($this));
        }
        return $this;
    }


    public function runInputIndicators(bool $force_rerun = false)
    {
        if (!$inds = $this->getOrAddInputIndicators()) {
            return $this;
        }
        foreach ($inds as $ind) {
            $ind->addRef($this);
            //dump($this->getShortClass().' running '.$ind->getShortClass().($force_rerun ? ' forced' : ''));
            $ind->checkAndRun($force_rerun);
        }
        return $this;
    }


    public function extract(Series $candles, string $index_type = 'sequential')
    {
        $out = [];
        foreach ($this->getInputs() as $input) {
            //dd($this->getShortClass().' HasInputs::extract() input '.$input, debug_backtrace());
            $out[$input] = $candles->extract($input, $index_type);
        }
        //dd($out);
        return $out;
    }
}
