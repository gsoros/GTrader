<?php

namespace GTrader\Indicators;

use GTrader\Indicator;
use GTrader\Exchange;
use GTrader\UserExchangeConfig;

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
        $mode = $this->getParam('indicator.mode');

        if (!in_array($mode, array('dynamic', 'fixed')))
            throw new \Exception('Mode must be either dynamic or fixed.');

        $owner = $this->getOwner();

        $exchange = Exchange::make($this->getCandles()->getParam('exchange'));
        $config = UserExchangeConfig::firstOrNew([
                                'exchange_id' => $exchange->getId(),
                                'user_id' => $owner->getParam('user_id', 0)]);

        // Get defaults from exchange config file
        $leverage = $exchange->getParam('leverage');
        $position_size = $exchange->getParam('position_size');

        // Update values from UserExchangeCOnfig
        if (is_array($config->options))
        {
            if (isset($config->options['leverage']))
                $leverage = $config->options['leverage'];
            if (isset($config->options['position_size']))
                $position_size = $config->options['position_size'];
        }

        $signal_ind = $owner->getSignalsIndicator();
        $signal_sig = $signal_ind->getSignature();
        $signal_ind->checkAndRun($force_rerun);

        $signature = $this->getSignature();

        $capital = $this->getParam('indicator.initial_capital');
        $upl = 0;
        $stake = $capital * $position_size / 100;
        $fee_multiplier = $exchange->getParam('fee_multiplier');
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
                    if ($prev_signal['signal'] == 'long')
                        $upl = $stake / $prev_signal['price'] *
                                ($candle->close - $prev_signal['price']) * $leverage;
                    else if ($prev_signal['signal'] == 'short')
                        $upl = $stake / $prev_signal['price'] *
                                ($prev_signal['price'] - $candle->close) * $leverage;
            }

            if ($signal = $candle->$signal_sig)
            {
                if ($signal['signal'] == 'long' && $capital > 0)
                { // go long
                    if ($prev_signal && $prev_signal['signal'] == 'short')
                    { // close last short
                        if ($prev_signal['price']) // avoid division by zero
                            $capital += $stake / $prev_signal['price'] * ($prev_signal['price']
                                        - $signal['price']) * $leverage;
                        $upl = 0;
                    }
                    if ($mode == 'dynamic') $stake = $capital * $position_size / 100;
                    // open long
                    $capital -= $stake * $fee_multiplier;
                }
                else if ($signal['signal'] == 'short' && $capital > 0)
                { // go short
                    if ($prev_signal && $prev_signal['signal'] == 'long')
                    { // close last long
                        if ($prev_signal['price']) // avoid division by zero
                            $capital += $stake / $prev_signal['price'] * ($signal['price']
                                        - $prev_signal['price']) * $leverage;
                        $upl = 0;
                    }
                    if ($mode == 'dynamic') $stake = $capital * $position_size / 100;
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
