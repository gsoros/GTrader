<?php

namespace GTrader\Indicators;

use Illuminate\Support\Facades\Auth;
use GTrader\Exchange;
use GTrader\UserExchangeConfig;
use GTrader\Log;
use GTrader\Gene;

class Balance extends HasInputs
{

    public function __construct(array $params = [])
    {
        //dump('Balance::__construct() '.$this->oid());
        parent::__construct($params);
        $this->setAllowedOwners(['GTrader\\Series']);
    }


    public function init()
    {
        //dump('Balance::init() '.$this->oid());
    }


    public function createDependencies()
    {
        if (!$this->getParam('indicator.input_signal')) {
            if (!$s = $this->getOwner()->getFirstIndicatorByClass('Signals')) {
                if (!$s = $this->getOwner()->getOrAddIndicator('Signals')) {
                    return $this;
                }
            }
            $s->addRef($this);
            $this->setParam('indicator.input_signal', $s->getSignature());
        }
        return $this;
    }


    public function crossover(Gene $other, float $weight = .5): Gene
    {
        return $this;
    }


    public function mutate(float $rate, int $max_nesting): Gene
    {
        return $this;
    }


    public function calculate(bool $force_rerun = false)
    {
        //Log::sparse('Balance::calculate() '.$this->oid());

        $this->runInputIndicators($force_rerun);

        $candles = $this->getCandles();

        //dump('Balance->calc '.date($f = 'Y-m-d H:i', $candles->first()->time).' - '.date($f, $candles->last()->time));

        $mode = $this->getParam('indicator.mode');
        if (!in_array($mode, ['dynamic', 'fixed'])) {
            Log::error('Mode must be either dynamic or fixed.');
            return $this;
        }

        $user_id = Auth::id();
        $exchange = Exchange::make($candles->getParam('exchange'));
        $exchange_id = $exchange->getId();

        if (!$config = UserExchangeConfig::statCached('user_exchange_config_'.$user_id.'_'.$exchange_id)) {
            $config = UserExchangeConfig::firstOrNew([
                'exchange_id' => $exchange_id,
                'user_id' => $user_id
            ]);
            UserExchangeConfig::statCache('user_exchange_config_'.$user_id.'_'.$exchange_id, $config);
        }

        // Get defaults from exchange config file
        $leverage = $exchange->getParam('leverage');
        $position_size = $exchange->getParam('position_size');

        // Update values from UserExchangeCOnfig
        if (is_array($config->options)) {
            if (isset($config->options['leverage'])) {
                $leverage = $config->options['leverage'];
            }
            if (isset($config->options['position_size'])) {
                $position_size = $config->options['position_size'];
            }
        }

        if (!($signal_ind = $this->getOwner()->getOrAddIndicator(
            $this->getParam('indicator.input_signal')
        ))) {
            Log::error('Signal indicator not found.');
            return $this;
        }
        $signal_key = $candles->key($signal_ind->getSignature('signal'));
        $signal_price_key = $candles->key($signal_ind->getSignature('price'));

        $output_key = $candles->key($this->getSignature());

        $capital = floatval($this->getParam('indicator.capital'));
        $upl = 0;
        $stake = $capital * $position_size / 100;
        $fee_multiplier = $exchange->getParam('fee_multiplier');
        $liquidated = false;
        $signal = null;
        $prev_signal = null;

        $candles->reset();

        while ($candle = $candles->next()) {
            if ($liquidated) {
                $candle->$output_key = 0;
                continue;
            }

            if ($prev_signal) {
                // update UPL
                if ($candle->close != $prev_signal['price']) {
                    // avoid division by zero
                    if ($prev_signal['signal'] == 'long') {
                        $upl = $stake / $prev_signal['price'] *
                                ($candle->close - $prev_signal['price']) * $leverage;
                    } elseif ($prev_signal['signal'] == 'short') {
                        $upl = $stake / $prev_signal['price'] *
                                ($prev_signal['price'] - $candle->close) * $leverage;
                    }
                }
            }
            if (isset($candle->$signal_key) &&
                isset($candle->$signal_price_key)) {
                if (($signal = $candle->$signal_key) &&
                    ($signal_price = $candle->$signal_price_key)) {
                    if ($signal == 'long' && $capital > 0) {
                        // go long
                        if ($prev_signal && $prev_signal['signal'] == 'short') {
                            // close last short
                            if ($prev_signal['price']) {
                                // avoid division by zero
                                $capital +=
                                    $stake / $prev_signal['price'] *
                                    ($prev_signal['price'] - $signal_price) *
                                    $leverage;
                                // closing a position involves a fee
                                $capital -= $stake * $fee_multiplier;
                            }
                            $upl = 0;
                        }
                        if ($mode == 'dynamic') {
                            $stake = $capital * $position_size / 100;
                        }
                        // open long: opening a position involves a fee
                        $capital -= $stake * $fee_multiplier;
                    } elseif ($signal == 'short' && $capital > 0) {
                        // go short
                        if ($prev_signal && $prev_signal['signal'] == 'long') {
                            // close last long
                            if ($prev_signal['price']) {
                                // avoid division by zero
                                $capital +=
                                    $stake / $prev_signal['price'] *
                                    ($signal_price - $prev_signal['price']) *
                                    $leverage;
                                // closing a position involves a fee
                                $capital -= $stake * $fee_multiplier;
                            }
                            $upl = 0;
                        }
                        if ($mode == 'dynamic') {
                            $stake = $capital * $position_size / 100;
                        }
                        // open short: opening a position involves a fee
                        $capital -= $stake * $fee_multiplier;
                    } elseif ($signal == 'neutral') {
                        // close last position
                        if ($prev_signal && $prev_signal['signal'] == 'long') {
                            // close last long
                            if ($prev_signal['price']) {
                                // avoid division by zero
                                $capital +=
                                    $stake / $prev_signal['price'] *
                                    ($signal_price - $prev_signal['price']) *
                                    $leverage;
                                // closing a position involves a fee
                                $capital -= $stake * $fee_multiplier;
                            }
                            $upl = 0;
                        }
                        elseif ($prev_signal && $prev_signal['signal'] == 'short') {
                            // close last short
                            if ($prev_signal['price']) {
                                // avoid division by zero
                                $capital +=
                                    $stake / $prev_signal['price'] *
                                    ($prev_signal['price'] - $signal_price) *
                                    $leverage;
                                // closing a position involves a fee
                                $capital -= $stake * $fee_multiplier;
                            }
                            $upl = 0;
                        }

                    }
                    $prev_signal = [
                        'signal' => $signal,
                        'price' => $signal_price,
                    ];
                }
            }
            $new_balance = $capital + $upl;
            if ($new_balance <= 0) {
                $liquidated = true;
                $new_balance = 0;
            }

            /* if ($signal) {
                $candle->$output_key = $new_balance;
            }
            elseif (!$prev_signal) {
                $candle->$output_key = $capital;
            } */
            $candle->$output_key = $new_balance;
            $signal = null;
        }
        return $this;
    }
}
