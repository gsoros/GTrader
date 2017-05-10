<?php

namespace GTrader\Indicators;

use GTrader\Indicator;

class Profitability extends Indicator
{
    protected $allowed_owners = ['GTrader\\Strategy'];

    public function createDependencies()
    {
        $owner = $this->getOwner();
        if (is_object($owner)) {
            /* just calling the owner's method will create the dependency */
            $owner->getSignalsIndicator();
        }
        return $this;
    }


    public function calculate(bool $force_rerun = false)
    {
        $owner = $this->getOwner();

        $signal_ind = $owner->getSignalsIndicator();
        $signal_sig = $signal_ind->getSignature();
        $signal_ind->checkAndRun($force_rerun);

        if (!$owner->hasIndicatorClass('Balance')) {
            error_log('Adding invisible balance indicator');
            $owner->addIndicator('Balance', ['display' => ['visible' => false]]);
        }
        $balance_ind = $owner->getFirstIndicatorByClass('Balance');
        $balance_sig = $balance_ind->getSignature();
        $balance_ind->checkAndRun($force_rerun);

        $signature = $this->getSignature();

        $prev_signal = false;
        $prev_balance = false;

        $winners = 0;
        $losers = 0;
        $score = 0;

        $candles = $this->getCandles();
        $candles->reset();

        while ($candle = $candles->next()) {

            if ($signal = $candle->$signal_sig) {
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
            $candle->$signature = $score;
        }

        return $this;
    }
}
