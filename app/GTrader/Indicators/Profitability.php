<?php

namespace GTrader\Indicators;

use GTrader\Indicator;

/* Winners vs. losers */
class Profitability extends Indicator
{
    use HasStrategy;

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->allowed_owners = ['GTrader\\Series'];
    }


    public function createDependencies()
    {
        if ($s = $this->getStrategy()) {
            $i = $s->getSignalsIndicator();
            $i->addRef($this);
        }
        return $this;
    }


    public function calculate(bool $force_rerun = false)
    {
        if (!$strategy = $this->getStrategy()) {
            return $this;
        }
        $candles = $this->getCandles();

        if (!($signal_ind = $strategy->getSignalsIndicator())) {
            return $this;
        }
        $signal_ind->checkAndRun($force_rerun);
        $signal_key = $candles->key($signal_ind->getSignature());

        if (!$candles->hasIndicatorClass('Balance')) {
            error_log('Profitability::calculate() adding balance indicator');
            $candles->addIndicator('Balance');
        }
        if (!($balance_ind = $candles->getFirstIndicatorByClass('Balance'))) {
            error_log('Profitability::calculate() could not find balance indicator');
            return $this;
        }
        $balance_ind->checkAndRun($force_rerun);
        $balance_sig = $candles->key($balance_ind->getSignature());

        $signature = $candles->key($this->getSignature());

        $prev_signal = false;
        $prev_balance = false;

        $winners = 0;
        $losers = 0;
        $score = 0;

        $candles->reset();

        while ($candle = $candles->next()) {

            if (isset($candle->$signal_key)) {
                if ($signal = $candle->$signal_key) {
                    if (in_array($signal['signal'], ['long', 'short'])) {
                        if ($prev_signal &&
                            $prev_signal['signal'] !== $signal['signal']) {

                            if (isset($candle->$balance_sig)) {
                                if ($candle->$balance_sig > $prev_balance) {
                                    $winners++;
                                } elseif ($candle->$balance_sig < $prev_balance) {
                                    $losers++;
                                }
                                $prev_balance = $candle->$balance_sig;
                            }
                        }
                    }
                    //$total = $winners + $losers;
                    //$score = $total ? $winners / $total * 100 + $winners: 0;
                    $score = $winners - $losers;

                    $prev_signal = $signal;
                }
            }
            $candle->$signature = $score;
        }

        return $this;
    }
}
