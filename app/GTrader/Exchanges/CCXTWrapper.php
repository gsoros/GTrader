<?php

namespace GTrader\Exchanges;

use Illuminate\Support\Arr;

use GTrader\Exchange;
use GTrader\Trade;
use GTrader\Log;
use ccxt\Exchange as CCXT;

class CCXTWrapper extends Exchange
{
    protected const CCXT_NAMESPACE = '\\ccxt\\';
    protected $ccxt = null;


    public function __construct(array $params = [])
    {
        parent::__construct($params);
        if ($id = $this->getParam('id')) {
            $this->ccxt($id);
        }
        //Log::debug($this->getParam('id', 'no id'));
    }


    protected function ccxt(string $id = '', bool $temporary = false)
    {
        $id = strlen($id) ? $id : $this->getParam('ccxt_id');
        $id = strlen($id) ? $id : null;

        $make_ccxt = function (string $id) {
            if (!$id) {
                throw new \Exception('Tried to make ccxt without id');
                return null;
            }
            $class = self::CCXT_NAMESPACE.$id;
            if (!class_exists($class)) {
                throw new \Exception($class.' does not exist');
                return null;
            }
            return new $class;
        };

        if ($temporary) {
            if (is_object($this->ccxt) && $this->ccxt->id == $id) {
                return $this->ccxt;
            }
            return $make_ccxt($id);
        }
        if (!is_object($this->ccxt) ||
            (
                $id &&
                is_object($this->ccxt) &&
                $this->ccxt->id !== $id
            )) {

            if ($id && is_object($this->ccxt) && $this->ccxt->id !== $id) {
                Log::debug('a different ccxt exists', $this->oid(), $this->ccxt->id, $id);
            }

            if ($id) {
                if (is_object($this->ccxt)) {
                    $this->cleanCache();
                }
                $this->ccxt = $make_ccxt($id);
            }
        }
        return $this->ccxt;
    }


    public function getId()
    {
        $name = $this->getShortClass();
        if (strlen($this->getParam('ccxt_id'))) {
            $name .= '_'.$this->getParam('ccxt_id');
        }
        return self::getIdByName($name);
    }


    public function getCcxtId(): string
    {
        if (!is_object($this->ccxt())) {
            throw new \Exception('ccxt not an object');
        }
        if (!strlen($this->ccxt()->id)) {
            throw new \Exception('ccxt id is empty');
        }
        return $this->ccxt()->id;
    }


    public function getSupported(array $options = []): array
    {
        // all exchanges supported by ccxt
        if (true === Arr::get($options, 'get_all')) {
            $CCXT = new CCXT;
            $exchanges = [];
            foreach ($CCXT::$exchanges as $id) {
                $exchange = $this->ccxt($id, true);
                $exchanges[] = $exchange;
            }
            //$exchanges = array_slice($exchanges, 0, 20);
            return $exchanges;
        }

        // exchanges chosen by the user

        return [$this];
    }


    public function form(array $options = [])
    {
        if ($this->getParam('ccxt_id')) {
            return parent::form($options);
        }

        $exchanges = $this->getSupported(['get_all' => true]);
        $supported = [];
        foreach ($exchanges as $exchange) {
            $supported[] = [
                'id' => $exchange->id,
                'name' => $exchange->name ?? 'unnamed',
            ];
        }
        //$supported = array_slice($supported, 0, 7);
        return view('Exchanges/CCXTWrapper_form', [
            'exchange' => $this,
            'supported_exchanges' => $supported,
        ]);
    }


    public function getInfo()
    {
        return view('Exchanges/CCXTInfo', [
            'exchange' => $this,
        ]);
    }


    public function getSymbols(array $options = []): array
    {
        $markets = $this->getCCXTProperty('markets', $options);
        if (!is_array($markets)) {
            Log::error('markets not array in '.$this->getShortClass(), $markets);
            return [];
        }
        $symbols = [];
        foreach ($markets as $market) {
            if (!isset($market['id'])) {
                Log::error('missing market id in '.$this->getShortClass(), $market);
                continue;
            }
            $symbols[$market['id']] = $market;
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
                Log::info('unmapped resolution: '.$key.' => '.$timeframe.' for '.$this->getId());
            }
            $resolutions[$resolution] = $key;
        }
        return $resolutions;
    }


    public function getTimeframes(array $options = []): array
    {
        $ret = $this->getCCXTProperty('timeframes', $options);
        return is_array($ret) ? $ret : [];
    }


    public function getCCXTProperty($prop, array $options = [])
    {
        if (!$this->cached($prop)) {
            if (!is_object($ccxt = $this->ccxt())) {
                return null;
            }
            $ccxt->loadMarkets();
            if (!isset($ccxt->$prop)) {
                return null;
            }
            $this->cache($prop, $ccxt->$prop);
        }
        return $this->cached($prop);
    }


    public function getName()
    {
        if (strlen($this->getParam('ccxt_id'))) {
            return $this->getCCXTProperty('name');
        }
        return parent::getName();
    }

    public function getSymbolName(string $symbol_id): string
    {
        $symbol = $this->getSymbol($symbol_id);
        if (!isset($symbol['symbol'])) {
            return $symbol_id;
        }
        return $symbol['symbol'];
    }


    public function getTicker(string $symbol) {}
    public function getCandles(
        string $symbol,
        int $resolution,
        int $since = 0,
        int $size = 0
    ) {}
    public function takePosition(
        string $symbol,
        string $signal,
        float $price,
        int $bot_id = null
    ) {}
    public function cancelUnfilledOrders(string $symbol, int $before_timestamp) {}
    public function saveFilledOrders(string $symbol, int $bot_id = null) {}

}
