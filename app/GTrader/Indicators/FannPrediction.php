<?php

namespace GTrader\Indicators;

use GTrader\Indicator;
use GTrader\Series;

class FannPrediction extends Indicator
{
    protected $_allowed_owners = ['GTrader\\Strategies\\Fann'];


    public function calculate()
    {        
        $signature = $this->getSignature();

        $owner = $this->getOwner();
        $num_samples = $owner->getNumSamples();
        $prediction = array();
        $owner->reset_pack();
        while ($pack = $owner->next_pack($num_samples)) 
        {
            $input = array();
            for ($i = 0; $i < $num_samples; $i++) 
            {
                if ($i < $num_samples - 1) 
                {
                    $input[] = floatval($pack[$i]->open);
                    $input[] = floatval($pack[$i]->high);
                    $input[] = floatval($pack[$i]->low);
                    $input[] = floatval($pack[$i]->close);
                }
                else 
                { // we only care about the open price for the last candle in the sample
                    $input[] = floatval($pack[$i]->open);
                }
            }
            $min = min($input);
            $max = max($input);
            foreach ($input as $k => $v) 
                $input[$k] = Series::normalize($v, $min, $max);
            //error_log(serialize($input));
            $pred = $owner->run_fann($input);
            $prediction[$pack[count($pack)-1]->time] = $pred;
        }
    
        $candles = $this->getCandles();
        $candles->reset();
        while ($candle = $candles->next()) 
        {
            $val = isset($prediction[$candle->time]) ? $prediction[$candle->time] : 0;
            //error_log('P:'.$val);
            //$price = series::ohlc4($candle);
            $price = $candle->open;
            $candle->$signature = $price + $price * $val * $owner->getParam('output_scaling') / 100;
            //$candles->set($candle);
        }
        return $this;
       
   }
}    
