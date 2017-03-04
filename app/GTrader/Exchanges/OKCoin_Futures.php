<?php

Namespace GTrader\Exchanges;

use GTrader\Exchange;

class OKCoin_Futures extends Exchange
{


    public function takePosition(string $position)
    {
        echo 'OKCoin_Futures going '.$position."\n";
    }



    /**
     * Get ticker.
     *
     * @param $params array
     * @return array
     */
    public function getTicker(array $params = [])
    {
        $new_params['symbol'] = $this->getParam('symbols.'.$params['symbol'].'.remote_name');
        if (!strlen($new_params['symbol']))
            throw new \Exception('Could not find remote name for '.$params['symbol']);

        $new_params['contract_type'] = $this->getParam('symbols.'.$params['symbol'].'.contract_type');
        if (!strlen($new_params['contract_type']))
            throw new \Exception('Could not find contract type for '.$params['symbol']);

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
        if (!isset($params['symbol']))
            throw new \Exception('Need symbol as parameter');

        $new_params['symbol'] = $this->getParam('symbols.'.$params['symbol'].'.remote_name');
        if (!strlen($new_params['symbol']))
            throw new \Exception('Could not find remote name for '.$params['symbol']);

        $new_params['contract_type'] = $this->getParam('symbols.'.$params['symbol'].'.contract_type');
        if (!strlen($new_params['contract_type']))
            throw new \Exception('Could not find contract type for '.$params['symbol']);

        if (!isset($params['size']))
            $params['size'] = 0;
        $new_params['size'] = $params['size'];

        $new_params['type'] = $this->resolution2name($params['resolution']);

        if (!isset($params['since']))
            $params['since'] = 0;
        $new_params['since'] = $params['since'].'000';

        $kline = \OKCoin::getFutureKline($new_params);

        return $kline;
    }


    private function resolution2name($resolution)
    {
        $resolution_names = $this->getParam('resolution_names');
        if (!array_key_exists($resolution, $resolution_names))
            throw new \Exception('Resolution '.$resolution.' not supported by '.__CLASS__);
        return $resolution_names[$resolution];
    }

}
