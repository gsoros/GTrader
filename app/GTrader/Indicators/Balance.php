<?php

namespace GTrader\Indicators;

use GTrader\Indicator;
use GTrader\Exchange;

class Balance extends Indicator
{
    protected $_allowed_owners = ['GTrader\\Strategy'];


    public function createDependencies()
    {
        $owner = $this->getOwner();
        if (is_object($owner))
            $owner->getSignalsIndicator(); /* just calling the owner's method will create the dependency */
        return $this;
    }


    public function calculate(bool $force_rerun = false)
    {
        $params = $this->getParam('indicator');

        $mode = $params['mode'];
        if (!in_array($mode, array('dynamic', 'fixed')))
            throw new \Exception('Mode must be either dynamic or fixed.');

        $owner = $this->getOwner();

        $signal_ind = $owner->getSignalsIndicator();
        $signal_sig = $signal_ind->getSignature();
        $signal_ind->checkAndRun($force_rerun);

        $signature = $this->getSignature();

        $capital = $params['initial_capital'];
        $upl = 0;
        $e = Exchange::make();
        $position_size = $e->getParam('position_size');
        $stake = $capital * $position_size;
        $fee_multiplier = $e->getParam('fee_multiplier');
        $leverage = $e->getParam('leverage');
        $liquidated = false;
        $prev_signal = false;

        $candles = $this->getCandles();
        $candles->reset();

        while ($candle = $candles->next())
        {
            if ($liquidated)
            {
                $candle->$signature = 0;
                continue;
            }

            if ($prev_signal)
            { // update UPL
                if ($candle->close != $prev_signal['price']) // avoid division by zero
                    if ($prev_signal['signal'] == 'buy')
                        $upl = $stake / $prev_signal['price'] *
                                ($candle->close - $prev_signal['price']) * $leverage;
                    else if ($prev_signal['signal'] == 'sell')
                        $upl = $stake / $prev_signal['price'] *
                                ($prev_signal['price'] - $candle->close) * $leverage;
            }

            if ($signal = $candle->$signal_sig)
            {
                if ($signal['signal'] == 'buy' && $capital > 0)
                { // go long
                    if ($prev_signal && $prev_signal['signal'] == 'sell')
                    { // close last short
                        if ($prev_signal['price']) // avoid division by zero
                            $capital += $stake / $prev_signal['price'] * ($prev_signal['price']
                                        - $signal['price']) * $leverage;
                        $upl = 0;
                    }
                    if ($mode == 'dynamic') $stake = $capital * $position_size;
                    // open long
                    $capital -= $stake * $fee_multiplier;
                }
                else if ($signal['signal'] == 'sell' && $capital > 0)
                { // go short
                    if ($prev_signal && $prev_signal['signal'] == 'buy')
                    { // close last long
                        if ($prev_signal['price']) // avoid division by zero
                            $capital += $stake / $prev_signal['price'] * ($signal['price']
                                        - $prev_signal['price']) * $leverage;
                        $upl = 0;
                    }
                    if ($mode == 'dynamic') $stake = $capital * $position_size;
                    // open short
                    $capital -= $stake * $fee_multiplier;
                }
                $prev_signal = $signal;
            }
            $new_balance = $capital + $upl;
            if ($new_balance <= 0) {
                    $liquidated = true;
                    $new_balance = 0;
            }
            $candle->$signature = $new_balance;
        }
        return $this;

   }
}
