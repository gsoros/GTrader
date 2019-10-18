<?php

namespace GTrader\Exchanges;

use Illuminate\Support\Arr;

use Illuminate\Http\Request;
use GTrader\UserExchangeConfig;
use GTrader\Exchange;
use GTrader\HasPCache;
use GTrader\Trade;
use GTrader\Log;
use ccxt\Exchange as CCXT;

class CCXTWrapper extends Exchange
{
    use HasPCache;

    protected const CCXT_NAMESPACE      = '\\ccxt\\';
    protected const CHILD_PREFIX        = 'CCXT_';
    protected const LOAD_MARKETS_BEFORE = ['markets', 'symbols'];
    protected $ccxt;


    public function __construct(array $params = [])
    {
        parent::__construct($params);
        if ($ccxt_id = $this->getParam('ccxt_id')) {
            $this->ccxt($ccxt_id);
        }
        //$this->setParam('pcache.log', 'all');
        //Log::debug($this->getParam('default_child', 'no default_child'));
    }


    protected function ccxt(string $ccxt_id = '', bool $temporary = false)
    {
        $ccxt_id = strlen($ccxt_id) ? $ccxt_id : $this->getParam('ccxt_id');
        $ccxt_id = strlen($ccxt_id) ? $ccxt_id : null;

        $make_ccxt = function (string $ccxt_id) {
            if (!$ccxt_id) {
                throw new \Exception('Tried to make ccxt without ccxt_id');
                return null;
            }
            $class = self::CCXT_NAMESPACE.$ccxt_id;
            if (!class_exists($class)) {
                throw new \Exception($class.' does not exist');
                return null;
            }
            return new $class([
                'enableRateLimit' => true,
            ]);
        };

        if ($temporary) {
            if (is_object($this->ccxt) && $this->ccxt->id == $ccxt_id) {
                return $this->ccxt;
            }
            return $make_ccxt($ccxt_id);
        }
        if (!is_object($this->ccxt) ||
            (
                $ccxt_id &&
                is_object($this->ccxt) &&
                $this->ccxt->id !== $ccxt_id
            )) {

            if ($ccxt_id && is_object($this->ccxt) && $this->ccxt->id !== $ccxt_id) {
                Log::debug('a different ccxt exists', $this->oid(), $this->ccxt->id, $ccxt_id);
            }

            if ($ccxt_id) {
                if (is_object($this->ccxt)) {
                    $this->cleanCache();
                }
                $this->ccxt = $make_ccxt($ccxt_id);
            }
        }
        return $this->ccxt;
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
        if (strlen($this->getParam('ccxt_id'))) {
            return self::CHILD_PREFIX.$this->getParam('ccxt_id');
        }
        return $this->getShortClass();
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

        $get = Arr::get($options, 'get', ['self']);
        $all = in_array('all', $get);
        $self = in_array('self', $get);
        $configured = in_array('configured', $get);
        $user_id = Arr::get($options, 'user_id');

        //Log::debug($options, $all, $self, $configured, $user_id);

        if ($self) {
            $exchanges[] = $this;
        }

        if ($all || $configured) {
            $CCXT = new CCXT;
            foreach ($CCXT::$exchanges as $ccxt_id) {
                $exchange = self::make(get_class($this), ['ccxt_id' => $ccxt_id]);
                if ($configured) {
                    $config = UserExchangeConfig::select('options');
                    if ($user_id) {
                        $config->where('user_id', $user_id);
                    }
                    $config->where('exchange_id', $exchange->getId());
                    if (!$config->value('options')) {
                        continue;
                    }
                }
                $exchanges[] = $exchange;
            }
            //$exchanges = array_slice($exchanges, 0, 20);
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

        return view('Exchanges/CCXTWrapperForm', [
            'exchange'              => $this,
            'supported_exchanges'   => $exchanges,
            'supported_exchange_ids' => $ids,
        ]);
    }


    public function getListItem()
    {
        if ($this->getParam('ccxt_id')) {
            return view('Exchanges/CCXTWrapperChildListItem', ['exchange' => $this]);
        }
        return view('Exchanges/CCXTWrapperListItem', ['exchange' => $this]);
    }


    public function getInfo()
    {
        return view('Exchanges/CCXTInfo', ['exchange' => $this]);
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


    public function getCCXTProperty(string $prop, array $options = [])
    {
        if ($val = $this->cached($prop)) {
            return $val;
        }
        $pcache_key = $ccxt = null;
        if (in_array($prop, self::LOAD_MARKETS_BEFORE)) {
            $pcache_key = 'CCXT_'.$this->getParam('ccxt_id').'_'.$prop;
            if ($val = $this->pCached($pcache_key)) {
                $this->cache($prop, $val);
                return $val;
            }
            if (!is_object($ccxt = $this->ccxt())) {
                Log::debug('ccxt not obj, wanted ', $prop);
                return null;
            }
            try {
                Log::debug('loadMarkets() for '.$this->getParam('ccxt_id'));
                $ccxt->loadMarkets();
            } catch (\Exception $e) {
                Log::debug('loadMarkets() failed for '.$this->getParam('ccxt_id'), $e->getMessage());
                Log::debug('checking pcache without age');
                if ($val = $this->pCached($pcache_key, null, -1)) {
                    Log::debug('pcache had an older entry');
                    $this->cache($prop, $val);
                    return $val;
                }
            }
        }
        if (!$ccxt && !is_object($ccxt = $this->ccxt())) {
            Log::debug('ccxt not obj, wanted ', $prop);
            return null;
        }
        if (!isset($ccxt->$prop)) {
            return null;
        }
        $this->cache($prop, $ccxt->$prop);
        if ($pcache_key) {
            $this->pCache($pcache_key, $ccxt->$prop);
        }
        return $ccxt->$prop;
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
        if (strlen($this->getParam('ccxt_id'))) {
            return $this->getVirtualClassName();
        }
        return 'CCXT';
    }


    public function getLongName()
    {
        if (strlen($this->getParam('ccxt_id'))) {
            return $this->getCCXTProperty('name') ?? $this->getVirtualClassName();
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
        /*
        $options = $request->options ?? [];
        if (isset($options['symbols']) && is_array($options['symbols'])) {
            foreach (array_keys($options['symbols']) as $symbol) {
                $symbol_id = self::getOrCreateSymbolId(
                    $symbol,
                    $this->getSymbolLongName($symbol)
                );
            }
        }
        */
        return parent::handleSaveRequest($request, $config);
    }


    public function fetchCandles(
        string $symbol,
        int $resolution,
        int $since = 0,
        int $size = 0
    )
    {
        $remote_resolution = $this->getRemoteResolution($resolution);
        Log::debug($this->getParam('ccxt_id'), $symbol, $remote_resolution, $since, $size);
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
