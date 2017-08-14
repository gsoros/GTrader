<?php

namespace GTrader\Indicators;

use GTrader\Log;

/* Winners vs. losers */
class Profitability extends HasInputs
{
    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->setAllowedOwners(['GTrader\\Series']);
    }


    public function createDependencies()
    {
        if (!$this->getParam('indicator.input_signal')) {
            if (!$s = $this->getOwner()->getOrAddIndicator('Signals')) {
                return $this;
            }
            $s->addRef($this);
            $this->setParam('indicator.input_signal', $s->getSignature());
        }
        return $this;
    }


    public function calculate(bool $force_rerun = false)
    {
        $candles = $this->getCandles();

        if (!($signal_ind = $this->getOwner()->getOrAddIndicator(
            $this->getParam('indicator.input_signal')
        ))) {
            Log::error('Signal indicator not found.');
            return $this;
        }
        $signal_key = $candles->key($signal_ind->getSignature('signal'));
        $signal_price_key = $candles->key($signal_ind->getSignature('price'));

        if (!($balance_ind = $this->getOwner()->getOrAddIndicator(
            'Balance',
            ['input_signal' => $this->getParam('indicator.input_signal')]
        ))) {
            Log::error('Balance indicator not found.');
            return $this;
        }
        $balance_ind->checkAndRun($force_rerun);
        $balance_key = $candles->key($balance_ind->getSignature());

        $output_key = $candles->key($this->getSignature());

        $prev_signal = false;
        $prev_balance = false;

        $winners = 0;
        $losers = 0;
        $score = 0;

        $candles->reset();

        while ($candle = $candles->next()) {
            if (isset($candle->$signal_key) &&
                isset($candle->$signal_price_key)) {
                if (($signal = $candle->$signal_key) &&
                    ($signal_price = $candle->$signal_price_key)) {
                    if (in_array($signal, ['long', 'short'])) {
                        if ($prev_signal &&
                            $prev_signal['signal'] !== $signal) {
                            if (isset($candle->$balance_key)) {
                                if ($candle->$balance_key > $prev_balance) {
                                    $winners++;
                                } elseif ($candle->$balance_key < $prev_balance) {
                                    $losers++;
                                }
                                $prev_balance = $candle->$balance_key;
                            }
                        }
                    }
                    $score = $winners - $losers;
                    $prev_signal = [
                        'signal' => $signal,
                        'price' => $signal_price,
                    ];
                }
            }
            $candle->$output_key = $score;
        }
        return $this;
    }
}
