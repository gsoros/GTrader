<?php

namespace GTrader\Indicators;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GTrader\Strategy;

class Signals extends HasInputs
{

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->allowed_owners = ['GTrader\\Series'];
    }

    public function init()
    {
        if ($strategy_id = $this->getParam('indicator.strategy_id')) {
            //dump('Signals::init() strategy_id = '.$strategy_id);
        }
        return parent::init();
    }

    public function getForm(array $params = [])
    {
        if ($user_id = Auth::id()) {
            $this->setParam('adjustable.strategy_id.options',
                array_replace(
                    $this->getParam('adjustable.strategy_id.options'),
                    Strategy::getStrategiesOfUser($user_id)
                )
            );
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

    public function getDisplaySignature(string $format = 'long', string $output = null)
    {
        $name = parent::getDisplaySignature('short');
        if ('short' === $format) {
            return $name;
        }
        if (!$strategy_id = $this->getParam('indicator.strategy_id')) {
            return ($param_str = $this->getParamString(['strategy_id'])) ?
                $name.' ('.$param_str.')' :
                $name;
        }

        $strategy_name = Strategy::statCached('id_'.$strategy_id.'_name');
        if (!$strategy_name) {
            if ($strategy = Strategy::statCached('id_'.$strategy_id)) {
                $strategy_name = $strategy->getParam('name');
            }
        }
        if (!$strategy_name) {
            if ($strategy_name = DB::table('strategies')
                    ->select('name')
                    ->where('id', $strategy_id)
                    ->where('user_id', Auth::id())
                    ->first()
                    ->name) {
                Strategy::statCache('id_'.$strategy_id.'_name', $strategy_name);
            } else {
                $strategy_name = 'Unknown Strategy';
            }
        }
        return $name.' ('.$strategy_name.')';
    }

    public function calculate(bool $force_rerun = false)
    {
        if ($strategy_id = $this->getParam('indicator.strategy_id')) {
            //dump('Signals::calculate() strategy_id = '.$strategy_id);
            if (!$strategy = Strategy::load($strategy_id)) {
                error_log('Signals::calculate() could not load strategy');
                return $this;
            }
            if (!$i = $strategy->getSignalsIndicator()) {
                error_log('Signals::calculate() could not load signals ind from strat');
                return $this;
            }
            $this->setParam('indicator', $i->getParam('indicator'));
            $this->setParam('indicator.strategy_id', $strategy_id);
        }

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

            foreach (['long', 'short'] as $signal) {
                if (!isset($candle->{$input_keys['input_'.$signal.'_a']}) ||
                    !isset($candle->{$input_keys['input_'.$signal.'_b']}) ||
                    !isset($candle->{$input_keys['input_'.$signal.'_source']})) {
                    error_log('Signals::calculate() missing input');
                    //dd($this);
                    continue;
                }
                if ($this->conditionMet(
                        $candle->{$input_keys['input_'.$signal.'_a']},
                        $conditions[$signal],
                        $candle->{$input_keys['input_'.$signal.'_b']}
                    ) &&
                    $previous['signal'] !== $signal) {
                    if ($previous['time'] === $candle->time) {
                        error_log('Signals::calculate() multiple conditions met for '.$candle->time);
                        continue;
                    }
                    $candle->{$output_keys['signal']} = $signal;
                    $candle->{$output_keys['price']} = $candle->{$input_keys['input_'.$signal.'_source']};

                    $previous = [
                        'time' => $candle->time,
                        'signal' => $signal,
                    ];
                }
            }
        }
        return $this;
    }

    protected function conditionMet($a, $cond, $b)
    {
        switch ($cond) {
            case '<':
                return $a < $b;
            case '>':
                return $a > $b;
        }
        error_log('Signals::conditionMet() unknown condition: '. $cond);
        return false;
    }
}
