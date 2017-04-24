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

        $signature = $this->getSignature();

        $prev_signal = false;

        $winners = 0;
        $losers = 0;
        $score = 50;

        $candles = $this->getCandles();
        $candles->reset();

        while ($candle = $candles->next()) {

            if ($signal = $candle->$signal_sig) {
                if ($signal['signal'] == 'long') {
                    if ($prev_signal && $prev_signal['signal'] == 'short') {
                        if ($signal['price'] < $prev_signal['price']) {
                            $winners++;
                        } elseif ($signal['price'] > $prev_signal['price']) {
                            $losers++;
                        }
                    }
                } elseif ($signal['signal'] == 'short') {
                    if ($prev_signal && $prev_signal['signal'] == 'long') {
                        if ($signal['price'] > $prev_signal['price']) {
                            $winners++;
                        } elseif ($signal['price'] < $prev_signal['price']) {
                            $losers++;
                        }
                    }
                }
                $total = $winners + $losers;
                $score = $total ? $winners / $total * 100 : 50;

                $prev_signal = $signal;
            }
            $candle->$signature = $score;
        }

        return $this;
    }
}
