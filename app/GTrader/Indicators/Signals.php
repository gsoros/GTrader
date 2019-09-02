<?php

namespace GTrader\Indicators;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GTrader\Strategy;
use GTrader\Util;
use GTrader\Log;

class Signals extends HasInputs
{

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->setAllowedOwners(['GTrader\\Series', 'GTrader\\Strategy']);
    }


    public function init()
    {
        $this->setParam(
            'indicator.strategy_id',
            intval($this->getParam('indicator.strategy_id'))
        );
        return parent::init();
    }


    public function createDependencies()
    {
        $this->copyParamsFromStrategy();
        return parent::createDependencies();
    }


    protected function copyParamsFromStrategy()
    {
        // Custom Settings
        if (0 == ($strategy_id = $this->getParam('indicator.strategy_id'))) {
            return $this;
        }

        if (!$owner = $this->getOwner()) {
            Log::error('could not get owner');
            return $this;
        }
        // Get Strategy from Parent
        if (0 > $strategy_id) {
            if (!$strategy = $owner->getStrategy()) {
                Log::error('could not get strat from owner');
                return $this;
            }
        } else { // Selected Strategy
            if (!$strategy = Strategy::load($strategy_id)) {
                Log::error('could not load strategy');
                return $this;
            }
        }
        if (!$i = $strategy->getSignalsIndicator(['set_prediction_id'])) {
            Log::error('could not load signals ind from strat');
            return $this;
        }
        // Copy params from strategy's signal ind
        $this->setParam('indicator', $i->getParam('indicator'));
        //dump('Signals::copyParamsFromStrategy() this: '.$this->debugObjId().' from strat: '.$i->debugObjId());
        // Except for strat_id
        $this->setParam('indicator.strategy_id', $strategy_id);
        // remove strategy's signal ind
        if ($i !== $this) {
            $owner->unsetIndicator($i);
        }
        return $this;
    }


    public function getForm(array $params = [])
    {
        $key = 'adjustable.strategy_id.options';
        if ($options = $this->getParam($key)) {
            if ($user_id = Auth::id()) {
                $this->setParam(
                    $key,
                    array_replace(
                        $options,
                        Strategy::getStrategiesOfUser($user_id)
                    )
                );
            }
        }
        return parent::getForm($params);
    }


    public function min(array $values)
    {
        return null;
    }


    public function max(array $values)
    {
        return null;
    }


    public function getSignature(string $output = null)
    {
        // Custom Settings
        if (0 == ($strategy_id = $this->getParam('indicator.strategy_id'))) {
            return parent::getSignature($output);
        }
        if (!$output) {
            $output = $this->getOutputs()[0];
        }
        $a = [
            'class' => $this->getShortClass(),
            'params' => [
                'strategy_id' => $strategy_id,
            ],
            'output' => $output,
        ];
        /*
        // Selected Strategy
        if (0 < $strategy_id) {
            return json_encode($a);
        }
        // Get Strategy from Parent
        if (!$owner = $this->getOwner()) {
            Log::error('Signals::getSignature() could not get owner');
            return json_encode($a);
        }
        if (!$strategy = $owner->getStrategy()) {
            //Log::error('Signals::getSignature() could not get strategy from owner');
            //dd('Signals::getSignature() could not get strategy from owner:', $this, $owner);
            return json_encode($a);
        }
        $a['params']['strategy_id'] = $strategy->getParam('id');
        */
        return json_encode($a);
    }


    public function getDisplaySignature(
      string $format = 'long',
      string $output = null,
      array $overrides = [])
    {
        $name = parent::getDisplaySignature('short');
        if ('short' === $format) {
            return $name;
        }
        // Get Strategy from Parent
        if (0 > ($strategy_id = $this->getParam('indicator.strategy_id'))) {
            $strat_name = 'No Strategy';
            if ($owner = $this->getOwner()) {
                if ($strategy = $owner->getStrategy()) {
                    $strat_name = $strategy->getParam('name', 'No Strategy');
                }
            }
            return $name.' (Auto: '.$strat_name.')';
        }
        // Custom Settings
        if (0 == ($strategy_id = $this->getParam('indicator.strategy_id'))) {
            return ($param_str = $this->getParamString(['strategy_id'])) ?
                $name.' ('.$param_str.')' :
                $name;
        }
        // Selected Strategy
        $strategy_name = Strategy::statCached('id_'.$strategy_id.'_name');
        if (!$strategy_name) {
            if ($strategy = Strategy::statCached('id_'.$strategy_id)) {
                $strategy_name = $strategy->getParam('name');
            }
        }
        if (!$strategy_name) {
            if ($strategy = DB::table('strategies')
                    ->select('name')
                    ->where('id', $strategy_id)
                    ->where('user_id', Auth::id())
                    ->first()) {
                if ($strategy_name = $strategy->name) {
                    Strategy::statCache('id_'.$strategy_id.'_name', $strategy_name);
                }
            }
        }
        return $name.' ('.($strategy_name ? $strategy_name : 'No Strategy').')';
    }


    public function calculate(bool $force_rerun = false)
    {
        //dump('1 '.$this->debugObjId());
        $this->copyParamsFromStrategy();
        $this->runInputIndicators($force_rerun);
        $candles = $this->getCandles();

        $output_keys = [
            'signal' => $candles->key($this->getSignature('signal')),
            'price' => $candles->key($this->getSignature('price')),
        ];

        $input_keys = [];
        foreach ($this->getInputs() as $input => $sig) {
            $input_keys[$input] = $candles->key($sig);
        }

        $conditions = [
            'long' => $this->getParam('indicator.long_cond'),
            'short' => $this->getParam('indicator.short_cond'),
        ];

        $previous = [
            'time' => 0,
            'signal' => '',
        ];

        $first_display_time = $candles->byKey($candles->getFirstKeyForDisplay())->time;

        // $dumptime = strtotime('2017-09-18 06:00:00');

        $candles->reset();
        while ($candle = $candles->next()) {
            if ($force_rerun) {
                if (isset($candle->{$output_keys['signal']})) {
                    unset($candle->{$output_keys['signal']});
                }
                if (isset($candle->{$output_keys['price']})) {
                    unset($candle->{$output_keys['price']});
                }
            }

            // do not emit signals if they won't be shown
            if ($candle->time < $first_display_time) {
                continue;
            }

            foreach (['long', 'short'] as $action) {
                foreach (['a', 'b', 'source'] as $component) {
                    if (!isset($candle->{$input_keys['input_'.$action.'_'.$component]})) {
                        Log::error('Missing input', $action, $component, $this->getParam('indicator'));
                        //dd($this);
                        return $this;
                    }
                }
                // if ($dumptime == $candle->time) {
                //     dump(
                //         $candle->{$input_keys['input_'.$action.'_a']}.' '.
                //         $conditions[$action].' '.
                //         $candle->{$input_keys['input_'.$action.'_b']}
                //     );
                // }
                if (Util::conditionMet(
                    $candle->{$input_keys['input_'.$action.'_a']},
                    $conditions[$action],
                    $candle->{$input_keys['input_'.$action.'_b']}
                ) && $previous['signal'] !== $action) {
                    if ($previous['time'] === $candle->time) {
                        //Log::debug('Multiple conditions met for '.$candle->time);
                        continue;
                    }
                    $candle->{$output_keys['signal']} = $action;
                    $candle->{$output_keys['price']} = $candle->{$input_keys['input_'.$action.'_source']};

                    $previous = [
                        'time' => $candle->time,
                        'signal' => $action,
                    ];
                }
            }
        }
        return $this;
    }
}
