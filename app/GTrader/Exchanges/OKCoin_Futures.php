<?php

Namespace GTrader\Exchanges;

use GTrader\Exchange;

class OKCoin_Futures extends Exchange
{
    

    
    /**
     * Get ticker.
     *
     * @param $params array
     * @return array
     */
    public function getTicker(array $params = [])
    {
        foreach (['symbol', 'contract_type'] as $param)
            $new_params[$param] = 
                isset($params[$param]) ?
                    $params[$param] :
                    $this->getParam($param);

        $response = \OKCoin::getFutureTicker($new_params);

        return get_object_vars($response->ticker);
    }
    

    /**
     * Get candles.
     *
     * @param $params array
     * @return array
     */
    public function getCandles(array $params = [])
    {
        foreach (['symbol', 'resolution', 'contract_type', 'size'] as $param)
            $new_params[$param] = 
                isset($params[$param]) ? 
                    $params[$param] : 
                    $this->getParam($param);
        $new_params['type'] = $this->resolution2type($new_params['resolution']);
        unset($new_params['resolution']);
        if (!isset($params['since'])) $params['since'] = 0;
        $new_params['since'] = $params['since'].'000';

        return \OKCoin::getFutureKline($new_params);
    }
    
    
    private function resolution2type($resolution) 
    {
        $resolutions = $this->getParam('resolutions');
        if (!array_key_exists($resolution, $resolutions))
            throw new Exception('Resolution '.$resolution.' not supported by '.__CLASS__);
        return $resolutions[$resolution];
    }

}
