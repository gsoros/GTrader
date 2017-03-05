<?php

Namespace GTrader\Exchanges;

use GTrader\Exchange;
use GTrader\Candle;

class OKCoin_Futures extends Exchange
{



    public function takePosition(string $symbol, string $position, float $price)
    {
        echo 'OKCoin_Futures going '.$position."\n";

        $userinfo = $this->getUserInfo();
        var_export($userinfo);

        if (!($remote_symbol = $this->getParam('symbols.'.$symbol.'.remote_name')))
            throw new \Exception('Remote name not found for '.$symbol);

        $positions = $this->getPositions($remote_symbol);
        var_export($positions);

        $currency = strtolower(substr($symbol, 0, 3));
        $position_size = $userinfo->info->$currency->rights *
                            $this->getUserOption('position_size') / 100;
        //if ($position_size > $userinfo->info->$currency->balance) return null;
        $position_contracts = floor($position_size * $price /
                                    $this->getParam('symbols.'.$symbol.'.contract_value') *
                                    $this->getUserOption('leverage'));
        if ($position_contracts > $this->getUserOption('max_contracts'))
            $position_contracts = $this->getUserOption('max_contracts');

        $contract_type = $this->getParam('symbols.'.$symbol.'.contract_type');

        $short_contracts_open = $long_contracts_open = 0;
        if (is_array($positions->holding))
            foreach ($positions->holding as $open_position)
                if ($open_position->contract_type == $contract_type)
                {
                    $short_contracts_open += $open_position->sell_amount;
                    $long_contracts_open += $open_position->buy_amount;
                }
        echo "\nBalance OK. Trade contracts: ".$position_contracts.' '.
                'Open: '.$long_contracts_open.' long, '.$short_contracts_open." short.\n";


        if ('long' === $position)
        {
            echo "\n*** Opening long ***\n";
            // close all short positions
            if (is_array($positions->holding))
                foreach ($positions->holding as $position)
                    if ($position->contract_type == $contract_type
                                    && $position->sell_amount)
                        $this->trade('close_short',
                                $price,
                                $position->sell_amount,
                                $remote_symbol,
                                $contract_type,
                                $this->getUserOption('leverage'),
                                $this->getUserOption('market_orders'));
            // open long position
            if ($long_contracts_open < $position_contracts
                                    && $position_contracts - $long_contracts_open > 0)
                    $reply = $this->trade('open_long',
                                $price,
                                $position_contracts - $long_contracts_open,
                                $remote_symbol,
                                $contract_type,
                                $this->getUserOption('leverage'),
                                $this->getUserOption('market_orders'));
        }

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
     * @param $params array ['since', 'resolution', 'symbol', 'size']
     * @return array of GTrader\Candle
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

        if (!is_array($kline)) return [];
        if (!count($kline)) return [];

        $exchange_id = $this->getId();
        if (!($symbol_id = self::getSymbolIdByExchangeSymbolName(
                                $this->getParam('local_name'),
                                $params['symbol'])))
            throw new \Exception('Could not find symbol ID for '.$params['symbol']);

        $candles = [];
        foreach($kline as $candle) {

            $new_candle = new Candle();
            $new_candle->open = $candle[1];
            $new_candle->high = $candle[2];
            $new_candle->low = $candle[3];
            $new_candle->close = $candle[4];
            $new_candle->volume = $candle[5];
            $new_candle->time = (int)substr($candle[0], 0, -3);
            $new_candle->exchange_id = $exchange_id;
            $new_candle->symbol_id = $symbol_id;
            $new_candle->resolution = $params['resolution'];
            $candles[] = $new_candle;
        }
        return $candles;
    }


    private function getUserInfo()
    {
        return \OKCoin::postFutureUserinfo4fix(
                $this->getUserOption('api_key'),
                $this->getUserOption('api_secret'));
    }


    private function trade()
    {
        echo "\ntrade()\n";
        var_export(func_get_args());
    }


    private function getPositions(string $remote_symbol)
    {
        return \OKCoin::postFuturePosition4fix(
                $this->getUserOption('api_key'),
                $this->getUserOption('api_secret'),
                [
                    'symbol' => $remote_symbol,
                    'type' => 1
                ]);
        // type -- by default, futures positions with leverage rate 10 are returned.
        // If type = 1, all futures positions are returned
    }






    private function resolution2name($resolution)
    {
        $resolution_names = $this->getParam('resolution_names');
        if (!array_key_exists($resolution, $resolution_names))
            throw new \Exception('Resolution '.$resolution.' not supported by '.__CLASS__);
        return $resolution_names[$resolution];
    }

}
