<?php

namespace GTrader\Exchanges\CCXT;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use GTrader\UserExchangeConfig;
use GTrader\Exchange;
use GTrader\Log;

class Supported extends Exchange
{
    use HasCCXT;

    protected const CLASS_PREFIX = 'CCXT\\';


    public function __construct(array $params = [])
    {
        //$this->setParam('ccxt_id', $this->getShortClass());
        //Log::debug($this->oid().' __construct()', $params, $this->getParams());
        parent::__construct($params);
    }


    public function getId()
    {
        return self::getOrAddIdByName(
            $this->getVirtualClassName(),
            $this->getLongName()
        );
    }


    public function getVirtualClassName()
    {
        return self::CLASS_PREFIX.$this->getParam('ccxt_id');
    }


    public function getListItem()
    {
        return view('Exchanges/CCXT/ListItem', ['exchange' => $this]);
    }


    public function getInfo()
    {
        return view('Exchanges/CCXT/Info', ['exchange' => $this]);
    }


    protected function getAllSymbols(): array
    {
        if (!$markets = $this->getCCXTProperty('markets')) {
            return [];
        }
        if (!is_array($markets)) {
            Log::error('markets not array in '.$this->getName(), $markets);
            return [];
        }
        $symbols = [];
        foreach ($markets as $market) {
            if (!isset($market['symbol'])) {
                Log::error('missing market symbol in '.$this->getShortClass(), $market);
                continue;
            }
            $symbols[$market['symbol']] = $market;
        }
        return $symbols;
    }


    /*
        In CCXT, there is a single list of resolutions per exchange
    */
    public function getResolutions(string $symbol_id = '', array $options = []): array
    {
        $timeframes = $this->getTimeframes($options);
        $resolutions = [];
        foreach ($timeframes as $key => $timeframe) {
            if (!$resolution = $this->getParam('resolution_map.'.$key)) {
                Log::info('unmapped resolution: '.$key.' => '.$timeframe.' for '.$this->getName());
            }
            $resolutions[$resolution] = $key;
        }
        return $resolutions;
    }


    public function getRemoteResolution(int $resolution): string
    {
        $resolutions = $this->getResolutions();
        $remote = $resolutions[$resolution] ?? strval($resolution);
        return $remote;
    }



    public function getTimeframes(array $options = []): array
    {
        $ret = $this->getCCXTProperty('timeframes', $options);
        return is_array($ret) ? $ret : [];
    }


    public function has($prop = null)
    {
        $has = $this->getCCXTProperty('has');
        if (!$prop) {
            return is_array($has) ? $has : [];
        }
        return is_array($has) && isset($has[$prop]) ? $has[$prop] : null;
    }


    public function getName()
    {
        return $this->getVirtualClassName();
    }


    public function getLongName()
    {
        return $this->getCCXTProperty('name') ?? $this->getVirtualClassName();
    }


    public function getSymbolName(string $symbol_id): string
    {
        $symbol = $this->getSymbol($symbol_id);
        if (!isset($symbol['symbol'])) {
            return $symbol_id;
        }
        return $symbol['symbol'];
    }


    public function getSymbolLongName(string $symbol_id): string
    {
        $symbol = $this->getSymbol($symbol_id);
        if (!isset($symbol['name'])) {
            return $symbol_id;
        }
        return $symbol['name'];
    }


    public function handleSaveRequest(Request $request, UserExchangeConfig $config)
    {

        $r_options = $request->options ?? [];
        $c_options = $config->options ?? [];
        if ($this->has('privateAPI')) {
            foreach (['apiKey', 'secret'] as $param) {
                if (isset($r_options[$param])) {
                    $c_options[$param] = $r_options[$param];
                }
            }
        }
        $config->options = $c_options;
        return parent::handleSaveRequest($request, $config);
    }


    public function form(array $options = [])
    {
        $this->setParam('user_id', Auth::id());
        return view('Exchanges/CCXT/Form', [
            'exchange' => $this,
            'options' => $options,
        ]);
    }


    public function fetchCandles(
        string $symbol,
        int $resolution,
        int $since = 0,
        int $size = 0
    )
    {
        $remote_resolution = $this->getRemoteResolution($resolution);
        Log::debug($this->getName(), $symbol, $remote_resolution, $since, $size);
        //$this->ccxt()->loadMarkets(); // why??
        $candles = $this->ccxt()->fetchOHLCV(
            $symbol,
            $remote_resolution,
            $since.'000',
            $size
        );
        //Log::debug($candles);

        if (!is_array($candles)) {
            return [];
        }
        if (!count($candles)) {
            return [];
        }

        $exchange_id = $this->getId();
        if (!($symbol_id = self::getSymbolIdByExchangeSymbolName(
            $this->getVirtualClassName(),
            $symbol
        ))) {
            throw new \Exception('Could not find symbol ID for '.
                $this->getVirtualClassName().' '.$symbol
            );
        }

        $return = [];
        foreach ($candles as $candle) {
            $new_candle = (object) [
                'open'          => $candle[1],
                'high'          => $candle[2],
                'low'           => $candle[3],
                'close'         => $candle[4],
                'volume'        => $candle[5],
                'time'          => (int)substr($candle[0], 0, -3),
                'exchange_id'   => $exchange_id,
                'symbol_id'     => $symbol_id,
                'resolution'    => $resolution,
            ];
            $return[] = $new_candle;
        }
        return $return;
    }


    public function takePosition(
        string $symbol,
        string $signal,
        float $price,
        int $bot_id = null
    )
    {

    }

    public function cancelUnfilledOrders(string $symbol, int $before_timestamp) {}
    public function saveFilledOrders(string $symbol, int $bot_id = null) {}

    public function getTicker(string $symbol) {}
}
