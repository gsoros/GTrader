<?php

namespace GTrader\Indicators;

use GTrader\Series;

class Signals extends HasInputs
{

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->allowed_owners = ['GTrader\\Series'];
    }


    public function min(array $values)
    {
        return null;
    }

    public function max(array $values)
    {
        return null;
    }


    public function calculate(bool $force_rerun = false)
    {
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
                        error_log('Signals::calculate() signal already set for '.$candle->time);
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
