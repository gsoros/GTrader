<?php

namespace GTrader\Exchanges\CCXT;

use Illuminate\Http\Request;

use GTrader\UserExchangeConfig;
use GTrader\Log;

class bitmex extends Supported
{
    public function __construct(array $params = [])
    {
        //$this->setParam('cache.log', 'all');
        //$this->setParam('pcache.log', 'all');

        parent::__construct($params);
    }


    public function handleSaveRequest(Request $request, UserExchangeConfig $config)
    {
        $r_options = $request->options ?? [];
        $c_options = $config->options ?? [];
        foreach (['leverage'] as $param) {
            if (isset($r_options[$param])) {
                $c_options[$param] = intval($r_options[$param]);
            }
        }
        $config->options = $c_options;
        return parent::handleSaveRequest($request, $config);
    }


    public function fetchPositions(string $symbol): array
    {
        $positions = [];
        $all_positions = $this->ccxt()->privateGetPosition();
        if (!is_array($all_positions)) {
            return $positions;
        }
        if (!$rsymbol = $this->getRemoteSymbol($symbol)) {
            Log::error('could not get remote symbol for ', $symbol);
            return $positions;
        }
        foreach ($all_positions as $pos) {
            if (($pos['symbol'] ?? null) === $rsymbol) {
                $positions[] = $pos;
            }
        }
        return $positions;
    }


    public function getPositionSum(string $symbol): float
    {
        $sum = 0.0;
        foreach ($this->fetchPositions($symbol) as $pos) {
            $sum += floatval($pos['currentQty'] ?? 0);
        }
        return $sum;
    }
}
