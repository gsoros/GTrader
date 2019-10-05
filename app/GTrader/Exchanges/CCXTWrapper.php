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
    protected const LOAD_MARKETS_BEFORE = ['markets', 'symbols'];
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
        $exchanges = [];

        $options = count($options) ? $options : ['get' => ['self' => true]];
        $get = Arr::get($options, 'get', []);
        $all = in_array('all', $get);
        $self = in_array('self', $get);
        $configured = in_array('configured', $get);
        $user_id = Arr::get($options, 'user_id');

        Log::debug($options, $all, $self, $configured, $user_id);

        if ($all || ($configured && $user_id)) {
            $CCXT = new CCXT;
            foreach ($CCXT::$exchanges as $ccxt_id) {
                $exchange = self::make(get_class($this), ['ccxt_id' => $ccxt_id]);
                if ($configured && $user_id) {
                    if (!$exchange->setParam('user_id', $user_id)->getUserOptions()) {
                        //Log::debug('user '.$user_id.' has no config for '.$exchange->getName());
                        continue;
                    }
                }
                $exchanges[] = $exchange;
            }
            //$exchanges = array_slice($exchanges, 0, 20);
        }

        if ($self) {
            $exchanges[] = $this;
        }

        return $exchanges;
    }


    public function form(array $options = [])
    {
        if ($this->getParam('ccxt_id')) {
            return parent::form($options);
        }

        $exchanges = $this->getSupported([
            'get' => ['all'],
        ]);
        $ids = [];
        foreach ($exchanges as $exchange) {
            $ids[] = $exchange->getParam('ccxt_id');
        };

        return view('Exchanges/CCXTWrapper_form', [
            'exchange'              => $this,
            'supported_exchanges'   => $exchanges,
            'supported_exchange_ids' => $ids,
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
            if (in_array($prop, self::LOAD_MARKETS_BEFORE)) {
                Log::debug('loading markets, because', $prop, self::LOAD_MARKETS_BEFORE);
                $ccxt->loadMarkets();
            }
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
        return 'CCXT';
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
