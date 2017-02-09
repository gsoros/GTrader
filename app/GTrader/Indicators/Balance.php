<?php

namespace GTrader\Indicators;

use GTrader\Indicator;
use GTrader\Exchange;

class Balance extends Indicator
{
    protected $_allowed_owners = ['GTrader\\Strategy'];



    public function calculate()
    {
        $params = $this->getParam('indicator');

        $mode = $params['mode'];
        if (!in_array($mode, array('dynamic', 'fixed')))
            throw new \Exception('Mode must be either dynamic or fixed.');

        $owner = $this->getOwner();
        
        // TODO get indicator class name from owner
        $indicator = 'FannSignals';
        if (!$owner->hasIndicator($indicator))
            $owner->addIndicator($indicator, ['display' => ['visible' => false]]);
        if (!($trade_ind = $owner->getIndicator($indicator)))
            throw new \Exception('Could not add indicator '.$indicator);
        $trade_sig = $trade_ind->getSignature();
        $trade_ind->checkAndRun();
        
        $signature = $this->getSignature();

        $capital = $params['initial_capital'];
        $upl = 0;
        $e = Exchange::make();
        $position_size = $e->getParam('position_size');
        $stake = $capital * $position_size;
        $fee_multiplier = $e->getParam('fee_multiplier');
        $leverage = $e->getParam('leverage');
        $liquidated = false;
        $prev_trade = false;

        $candles = $this->getCandles();
        $candles->reset();

        $prev_trade = false;
        while ($candle = $candles->next()) 
        {
            if ($liquidated) 
            {
                $candle->$signature = 0;
                continue;
            }

            if ($prev_trade) 
            { // update UPL
                if ($candle->close != $prev_trade['price']) // avoid division by zero
                    if ($prev_trade['signal'] == 'buy')
                        $upl = $stake / $prev_trade['price'] * 
                                ($candle->close - $prev_trade['price']) * $leverage;
                    else if ($prev_trade['signal'] == 'sell')
                        $upl = $stake / $prev_trade['price'] * 
                                ($prev_trade['price'] - $candle->close) * $leverage;
            }

            if ($trade = $candle->$trade_sig)
            {
                if ($trade['signal'] == 'buy' && $capital > 0) 
                { // go long
                    if ($prev_trade && $prev_trade['signal'] == 'sell') 
                    { // close last short
                        if ($prev_trade['price']) // avoid division by zero
                            $capital += $stake / $prev_trade['price'] * ($prev_trade['price'] 
                                        - $trade['price']) * $leverage;
                        $upl = 0;
                    }
                    if ($mode == 'dynamic') $stake = $capital * $position_size;
                    // open long
                    $capital -= $stake * $fee_multiplier;
                }
                else if ($trade['signal'] == 'sell' && $capital > 0) 
                { // go short
                    if ($prev_trade && $prev_trade['signal'] == 'buy') 
                    { // close last long
                        if ($prev_trade['price']) // avoid division by zero
                            $capital += $stake / $prev_trade['price'] * ($trade['price'] 
                                        - $prev_trade['price']) * $leverage;
                        $upl = 0;
                    }
                    if ($mode == 'dynamic') $stake = $capital * $position_size;
                    // open short
                    $capital -= $stake * $fee_multiplier;
                }
                $prev_trade = $trade;
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
