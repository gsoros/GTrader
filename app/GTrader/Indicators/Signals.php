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
            /* dump('copyParamsFromStrategy $strategy_id is 0',
                'this: '.$this->oid().
                ' owner: '.$this->getOwner()->oid()); */
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
        //dump('Signals::copyParamsFromStrategy() this: '.$this->oid().' from strat: '.$i->oid());
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


    public function getSignature(string $output = null, int $json_options = 0)
    {
        // Custom Settings
        if (0 == ($strategy_id = $this->getParam('indicator.strategy_id'))) {
            return parent::getSignature($output, $json_options);
        }
        if (!$output) {
            $output = $this->getOutputs()[0];
        }

        return json_encode(
            [
                'class' => $this->getShortClass(),
                'params' => [
                    'strategy_id' => $strategy_id,
                ],
                'output' => $output,
            ],
            $json_options
        );
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
        //dump('sc force '.$force_rerun.' for '.$this->oid());
        $this->copyParamsFromStrategy();
        $this->runInputIndicators($force_rerun);
        $candles = $this->getCandles();
        $resolution = $candles->getParam('resolution');
        $min_trade_distance = $this->getParam('indicator.min_trade_distance', 1);

        $output_keys = [
            'signal' => $candles->key($this->getSignature('signal')),
            'price' => $candles->key($this->getSignature('price')),
        ];

        $input_keys = $input_sigs = [];
        foreach ($this->getInputs() as $input => $sig) {
            //Log::debug($input);
            $input_keys[$input] = $candles->key($sig);
            $input_sigs[$input] = $sig;
        }

        $conditions = [];
        foreach (['open_long', 'close_long', 'open_short', 'close_short'] as $action) {
            $conditions[$action] = $this->getParam('indicator.'.$action.'_cond');
        }

        $previous_signal = [
            'time' => 0,
            'signal' => 'neutral',
        ];

        $previous_candle = null;

        $first_display_time = is_object(
            $c = $candles->byKey($candles->getFirstKeyForDisplay()))
            ? $c->time
            : 0;

        $actions = ['open', 'close'];
        $directions = ['long', 'short'];
        $components = ['a', 'b', 'source'];

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

            if (!$previous_candle) {
                $previous_candle = $candle;
                continue;
            }
            // do not emit signals if they won't be shown
            if ($candle->time < $first_display_time) {
                $previous_candle = $candle;
                continue;
            }

            if (('neutral' !==  $previous_signal['signal']) &&
                (1 < $min_trade_distance) &&
                ($previous_signal['time'] > $candle->time - $resolution * $min_trade_distance)) {
                    $previous_candle = $candle;
                    continue;
            }

            $met = [];
            foreach ($actions as $action) {
                foreach ($directions as $direction) {
                    $input_key = 'input_'. $action.'_'.$direction.'_';
                    // check for the key components ;-)
                    foreach ($components as $component) {
                        if (!isset($previous_candle->{$input_keys[$input_key.$component]})) {
                            Log::error(
                                'Missing input',
                                $action,
                                $direction,
                                $component,
                                $input_sigs[$input_key.$component]
                            );
                            return $this;
                        }
                    }

                    $met = array_merge_recursive($met, [
                        $action => [
                            $direction =>
                                Util::conditionMet(
                                    $candle->{$input_keys[$input_key.'a']},
                                    $conditions[$action.'_'.$direction],
                                    $candle->{$input_keys[$input_key.'b']}
                                    /*
                                    $previous_candle->{$input_keys[$input_key.'a']},
                                    $conditions[$action.'_'.$direction],
                                    $previous_candle->{$input_keys[$input_key.'b']}
                                    */
                                )
                            ],
                        ]
                    );
                }
            }

            $log = [
                'time' => $candle->time,
                'met' => $met,
            ];

            $source = [
                'neutral' => [
                    'long' => 'close_long',
                    'short' => 'close_short',
                ],
                'long' => [
                    'neutral' => 'open_long',
                    'short' => 'open_long',
                ],
                'short' => [
                    'neutral' => 'open_short',
                    'long' => 'open_short',
                ],
            ];

            $emit = function($signal)
                use (&$log, &$candle, &$previous_signal, $input_keys, $output_keys, $source)
            {
                $log['signal'] = $signal;
                $log['previous_signal'] = $previous_signal['signal'];
                $candle->{$output_keys['signal']} = $signal;
                $source_key = $source[$signal][$previous_signal['signal']];
                $candle->{$output_keys['price']} =
                    $candle->{$input_keys['input_'.$source_key.'_source']};
                $previous_signal = [
                    'time' => $candle->time,
                    'signal' => $signal,
                ];
            };

            if (
                 $met['open' ]['long' ] &&
                !$met['close']['long' ] &&
                !$met['open' ]['short'] &&
                'long' !== $previous_signal['signal']) {
                $emit('long');
            } elseif (
                 $met['close' ]['long' ] &&
                !$met['open']['long' ] &&
                'long' == $previous_signal['signal']) {
                $emit('neutral');
            } elseif (
                 $met['open' ]['short' ] &&
                !$met['close']['short' ] &&
                !$met['open' ]['long'] &&
                'short' !== $previous_signal['signal']) {
                $emit('short');
            } elseif (
                 $met['close' ]['short' ] &&
                !$met['open']['short' ] &&
                'short' == $previous_signal['signal']) {
                $emit('neutral');
            }

            $previous_candle = $candle;
            //Log::debug($log);
        }
        return $this;
    }


    /*
    protected function handleChange(string $before, string $after)
    {
        //Log::debug('Before: '.$before);
        //Log::debug('After: '.$after);
        return parent::handleChange($before, $after);
    }
    */

}
