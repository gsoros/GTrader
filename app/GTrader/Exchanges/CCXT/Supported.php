<?php

namespace GTrader\Exchanges\CCXT;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;

use GTrader\UserExchangeConfig;
use GTrader\Exchange;
use GTrader\Log;
use GTrader\Trade;

class Supported extends Exchange
{
    use HasCCXT;

    protected const CLASS_PREFIX = 'CCXT\\';


    public function __construct(array $params = [])
    {
        //$this->setParam('ccxt_id', $this->getShortClass());
        //Log::debug($this->oid().' __construct()', $params, $this->getParams());
        parent::__construct($params);

        if ($testnet = $this->getParam('has.testnet')) {
            if ($this->getUserOption('use_testnet')) {
                if (!$testnet_url = $this->getCCXTProperty(['urls', $testnet['urlKey'] ?? null]) ?? null) {
                    throw new \Exception('Could not obtain testnet API URL');
                }
                if (!$this->setCCXTProperty(['urls', 'api'], $testnet_url)) {
                    throw new \Exception('Could not set API URL to testnet API URL');
                }
            }
        }

        foreach (
            ['apiKey' => 'apiKey', 'secret' => 'secret']
            as $user_option => $ccxt_prop
        ) {
            $value = $this->getUserOption($user_option);
            if (!$this->setCCXTProperty($ccxt_prop, $value)) {
                throw new \Exception('Could not set '.$ccxt_prop.' to '.$value);
            }
        }
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


    protected function getAllSymbols(array $options = []): array
    {
        //Log::debug($options);
        if (!$markets = $this->getCCXTProperty('markets')) {
            return [];
        }
        if (!is_array($markets)) {
            Log::error('markets not array in '.$this->getName(), $markets);
            return [];
        }
        $active = in_array('active', $options['get'] ?? []);
        $symbols = [];
        foreach ($markets as $market) {
            if (!isset($market['symbol'])) {
                Log::error('missing market symbol in '.$this->getShortClass(), $market);
                continue;
            }
            if ($active && !$this->marketActive($market['symbol'])) {
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
        if ($testnet = $this->getParam('has.testnet')) {
            foreach (['use_testnet'] as $param) {
                if (isset($r_options[$param])) {
                    $c_options[$param] = $r_options[$param] ? 1 : 0;
                }
            }
        }
        if ($this->has('privateAPI')) {
            foreach (['apiKey', 'secret', 'order_type'] as $param) {
                if (isset($r_options[$param])) {
                    $c_options[$param] = $r_options[$param];
                }
            }
            foreach (['position_size'] as $param) {
                if (isset($r_options[$param])) {
                    $c_options[$param] = $r_options[$param]
                        ? intval($r_options[$param])
                        : 0;
                }
            }
        }
        $config->options = $c_options;
        return parent::handleSaveRequest($request, $config);
    }


    public function form(array $options = [])
    {
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
        //Log::debug($this->getName(), $symbol, $remote_resolution, $since, $size);

        $candles = $this->ccxt()->fetchOHLCV(
            $symbol,
            $remote_resolution,
            $since.'000',
            $size
        );
        //Log::debug($candles);

        if (!is_array($candles) || !count($candles)) {
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
        if (!in_array($signal, ['long', 'neutral', 'short'])) {
            Log::error('invalid signal', $signal);
            return $this;
        }
        if (!$user_id = $this->getParam('user_id')) {
            throw new \Exception('takePosition() requires user_id to be set');
            return $this;
        }
        if (!$market = $this->getMarket($symbol)) {
            throw new \Exception('could not get market');
            return $this;
        }
        if (!$this->marketActive($symbol)) {
            throw new \Exception('market is not active');
            return $this;
        }
        if (!$currency = $market['base']) {
            throw new \Exception('could not get base currency');
            return $this;
        }
        if (!$balance = $this->getTotalBalance($currency)) {
            Log::info('no balance');
            return $this;
        }
        if (!$position_size = $this->getUserOption('position_size')) {
            Log::info('position size not set by the user');
            return $this;
        }
        if (!$contract_value = $this->getContractValue($symbol)) {
            Log::info('could not get contract value for', $symbol);
            return $this;
        }

        if ('neutral' === $signal) {
            $target_position = 0;
        } else {
            $target_position = $balance * $position_size / 100;
            if ('short' === $signal) {
                $target_position = 0 - $target_position;
            }
        }

        $leverage = (1 <= ($l = intval($this->getUserOption('leverage'))) ? $l : 1);
        $target_contracts = floor($target_position * $price / $contract_value / $leverage);
        $current_contracts = $this->getPositionSum($symbol);
        $new_contracts = $target_contracts - $current_contracts;
        //dump($target_contracts, $current_contracts, $new_contracts, $leverage);

        if (!$new_contracts) {
            Log::info('nothing to buy or sell', $symbol);
            return $this;
        }
        if ($new_contracts < $current_contracts / 100) {
            Log::info('Less than 1% to change, aborting', $symbol);
            return $this;
        }
        $side = 0 < $new_contracts ? 'buy' : 'sell';
        $new_contracts = abs($new_contracts);
        $order_type = $this->getUserOption('order_type');
        if ('market' === $order_type) {
            $price = null;
        }
        else {
            $order_type = 'limit';
            $price = $this->formatNumber($price, $symbol, 'price');
        }
        $new_contracts = $this->formatNumber($new_contracts, $symbol, 'amount');
        $this->ccxt()->createOrder($symbol, $order_type, $side, $new_contracts, $price);
        //dump('createOrder()', $symbol, $order_type, $side, $new_contracts, $price);
        return $this;
    }


    protected function formatNumber(float $number, string $symbol, string $type): float
    {
        if (!in_array($type, ['price', 'amount'])) {
            Log::error('invalid type', $type);
            return $number;
        }
        if (!$market = $this->getMarket($symbol)) {
            Log::error('could not get market for', $symbol);
        } else {
            if (!$limits = $market['limits'] ?? false) {
                //Log::info('could not get limits for', $symbol);
            } elseif (!$limits = $limits[$type] ?? false) {
                //Log::info('could not get limits for', $symbol, $type);
            } else {
                if (!$min = $limits['min'] ?? false) {
                    //Log::info('could not get min for', $symbol, $type);
                } elseif ($number < $min) {
                    Log::warning('conversion to min', $symbol, $type, $number, $min);
                    $number = $min;
                }
                if (!$max = $limits['max'] ?? false) {
                    //Log::info('could not get max for', $symbol, $type);
                } elseif ($number > $max) {
                    Log::warning('conversion to max', $symbol, $type, $number, $max);
                    $number = $max;
                }
            }
        }
        return $this->ccxt()->{$type.'ToPrecision'}($symbol, $number);
    }


    // https://github.com/ccxt/ccxt/wiki/Manual#markets
    // "The active flag is not yet supported and/or implemented by all markets."
    // so we are defaulting to true
    public function marketActive(string $symbol): bool
    {
        if (!$market = $this->getMarket($symbol)) {
            return false;
        }
        return isset($market['active']) ? boolval($market['active']) : true;
    }


    public function cancelOpenOrders(string $symbol, int $before_timestamp = 0)
    {
        //dump($symbol, date('Y-m-d H:i:s', $before_timestamp));
        foreach ($this->fetchOrders($symbol, 'open', $before_timestamp) as $order) {
            $this->cancelOrder($order['id'] ?? '', $order['symbol'] ?? $symbol);
        }
        return $this;
    }


    public function cancelOrder(string $id, string $symbol)
    {
        $return = [];
        try {
            $return = $this->ccxt()->cancelOrder($id, $symbol);
        } catch (\Exception $e) {
            Log::info($id, $e->getMessage());
        }
        return $return;
    }


    public function fetchOrders(
        string $symbol,
        string $order_type = '', // '', 'any', 'open', 'closed'
        int $before_timestamp = 0): array
    {
        $orders = [];
        $order_types = ['' => '', 'any' => '', 'open' => 'Open', 'closed' => 'Closed'];
        if (!isset($order_types[$order_type])) {
            Log::error('wrong order_type', $order_type);
            return $orders;
        }
        $method = 'fetch'.$order_types[$order_type].'Orders';
        if (!$this->has($method) || !method_exists($this->ccxt(), $method)) {
            Log::info('Exchange has no method', $method, $this->getName());
            return $orders;
        }
        try {
            $orders = $this->ccxt()->$method($symbol);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }
        if (0 < $before_timestamp) {
            $before_timestamp *= 1000;
            foreach ($orders as $id => $order) {
                if (!isset($order['timestamp'])) {
                    continue;
                }
                if ($before_timestamp < $order['timestamp']) {
                    unset($orders[$id]);
                }
            }
        }
        return $orders;
    }


    public function fetchBalance(): array
    {
        try {
            //$this->ccxt()->loadMarkets();
            //$this->setCCXTProperty('verbose', true);
            $balance = $this->ccxt()->fetchBalance();
        } catch (\Exception $e) {
            Log::info('fetchBalance() failed', $e->getMessage());
            return [];
        }
        return is_array($balance) ? $balance : [];
    }


    public function getBalanceField(string $field, string $currency, $balance_arr = null): float
    {
        $nil = 0.0;
        if (!is_array($balance_arr)) {
            if (!count($balance_arr = $this->fetchBalance())) {
                Log::info('could not get balance for', $this->getName());
                //throw new \Exception('could not get balance for '.$this->getName());
                return $nil;
            }
        }
        if (!isset($balance_arr[$currency]) ||
            !is_array($balance_arr[$currency])) {
            Log::info('could not get balance for', $this->getName(), $currency);
            //throw new \Exception('could not get balance for '.$this->getName().', '.$currency);
            return $nil;
        }
        if (!isset($balance_arr[$currency][$field])) {
            Log::info('no such field', $this->getName(), $field, $currency);
            return $nil;
        }
        return $balance_arr[$currency][$field];
    }



    public function getFreeBalance(string $currency, $balance_arr = null): float
    {
        return $this->getBalanceField('free', $currency, $balance_arr);
    }


    public function getTotalBalance(string $currency, $balance_arr = null): float
    {
        return $this->getBalanceField('total', $currency, $balance_arr);
    }


    public function fetchPositions(string $symbol): array
    {
        $this->methodNotImplemented();
        return [];
    }


    public function getPositionSum(string $symbol): float
    {
        $this->methodNotImplemented();
        return 0.0;
    }


    public function getMarkets(): array
    {
        return is_array($markets = $this->getCCXTProperty('markets')) ? $markets : [];
    }


    public function getMarket(string $symbol): array
    {
        $markets = $this->getMarkets();
        return isset($markets[$symbol])
            ? is_array($markets[$symbol])
                ? $markets[$symbol]
                : []
            : [];
    }


    public function getRemoteSymbol(string $symbol): string
    {
        return strval($this->getSymbol($symbol)['id']) ?? '';
    }


    public function getContractValue(string $symbol): float
    {
        return 1.0;
    }


    public function isFutures(string $symbol): bool
    {
        $market = $this->getMarket($symbol);
        return isset($market['future']) ? boolval($market['future']) : false;
    }


    public function isSpot(string $symbol): bool
    {
        $market = $this->getMarket($symbol);
        return isset($market['spot']) ? boolval($market['spot']) : false;
    }


    public function isSwap(string $symbol): bool
    {
        $market = $this->getMarket($symbol);
        return isset($market['swap']) ? boolval($market['swap']) : false;
    }


    public function isLive()
    {
        return 'ok' === $this->getStatus()['status'];
    }


    public function getStatus(): array
    {
        if ($cached = $this->cached('status')) {
            $status = $cached;
        } else {
            if (!is_array($status = $this->ccxt()->fetchStatus())
                || !array_key_exists('status', $status)) {
                Log::info('wrong status received', $this->getName(), $status);
                return ['status' => 'error'];
            }
            $this->cache('status', $status);
        }
        return $status;
    }


    public function getMarketPropery(string $symbol, string $key)
    {
        $market = $this->getMarket($symbol);
        return Arr::get($market, $key);
    }


    public function getFee(string $symbol, string $type = 'taker'): float
    {
        return floatval($this->getMarketPropery($symbol, $type));
    }


    public function saveFilledTrades(string $symbol, int $bot_id = null)
    {
        if (!$user_id = $this->getParam('user_id')) {
            throw new \Exception('need user id');
        }
        if (!$this->has('fetchClosedOrders')) {
            Log::error('exchange does not support fetchClosedOrders', $this->getName());
            return $this;
        }
        $since = $this->getLastSavedTradeTime();
        $orders = [];
        try {
            $orders = $this->ccxt()->fetchClosedOrders(null, $since);
        } catch (\Exception $e) {
            Log::error('could not fetch closed orders', $this->getName(), $e->getMessage());
        }
        if (!is_array($orders) || !count($orders)) {
            return $this;
        }
        $leverage = $this->getUserOption('leverage', 1);
        foreach ($orders as $order) {
            //dump($order);
            $trade = Trade::firstOrNew(['remote_id' => $order['id']]);
            $trade->time = intval($order['timestamp']);
            $trade->remote_id = $order['id'];
            $trade->exchange_id = $this->getId();
            $trade->symbol_id = $this->getSymbolId(strval($order['symbol']));
            $trade->user_id = $user_id;
            $trade->bot_id = $bot_id;
            $trade->amount_ordered = $order['amount'];
            $trade->amount_filled = $order['filled'];
            $trade->price = $order['price'];
            $trade->avg_price = 0;
            $trade->action = $order['side'];
            $trade->type = $order['type'];
            $trade->fee = $order['fee']['cost'];
            $trade->fee_currency = $order['fee']['currency'];
            $trade->status = $order['status'];
            $trade->leverage = $leverage;
            $trade->contract = '';
            $trade->save();
        }
        return $this;
    }
}
