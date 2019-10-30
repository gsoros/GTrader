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
    protected $trade_environment;


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


    protected function tradeExtractSignal(): bool
    {
        $env = $this->trade_environment;
        $env->signal_time = intval($env->signal['time'] ?? 0);
        $env->price = floatval($env->signal['price'] ?? 0);
        $env->signal = strval($env->signal['signal'] ?? 'neutral');
        if (!in_array($env->signal, ['long', 'neutral', 'short'])) {
            Log::error($env->error = 'invalid signal', $env->signal);
            return false;
        }
        return true;
    }


    protected function tradeSetupEnvironment(): bool
    {
        $env = $this->trade_environment;
        if (!$env->user_id = $this->getParam('user_id')) {
            throw new \Exception($env->error = 'user_id required');
            return false;
        }
        if (!$env->symbol_id = $this->getSymbolId($env->symbol)) {
            throw new \Exception($env->error = 'could not get symbol_id for '.$env->symbol);
            return false;
        }
        /*
        if (!$env->market = $this->getMarket($env->symbol)) {
            throw new \Exception($env->error = 'could not get market '.$env->symbol);
            return false;
        }
        */
        if (!$this->marketActive($env->symbol)) {
            throw new \Exception($env->error = $this->getName().' '.$env->symbol.' is not active ');
            return false;
        }
        if (!$env->position_size = $this->getUserOption('position_size')) {
            Log::info($env->eror = 'position size not set by the user');
            return false;
        }
        if (!$env->unit_value = $this->getUnitValue($env->symbol)) {
            Log::info($env->error = 'could not get unit value for '.$env->symbol);
            return false;
        }
        $env->trade = null;
        try {
            $env->trade = Trade::where([
                ['symbol_id', $env->symbol_id],
                ['bot_id', $this->getParam('bot_id')],
                ['signal_time', $env->signal_time],
            ])->firstOrFail();
        } catch (\Exception $e) {
            Log::debug($this->getName().' Trade does not exist in the db');
        }
        $env->leverage = (1 <= ($l = intval($this->getUserOption('leverage'))) ? $l : 1);
        return true;
    }


    protected function tradeGetCurrency(): bool
    {
        $env = $this->trade_environment;
        if (!$env->currency = $this->getCurrency($env->symbol)) {
            throw new \Exception($env->error = 'could not get base currency');
            return false;
        }
        if (!$env->quote_currency = $this->getCurrency($env->symbol, 'quote')) {
            throw new \Exception($env->error = 'could not get quote currency');
            return false;
        }
        return true;
    }


    protected function tradeGetBalance(): bool
    {
        $env = $this->trade_environment;
        if (!$env->balance = $this->getTotalBalance($env->currency)) {
            Log::info($env->error = 'No '.$env->currency.' balance on '.$this->getName());
        }
        if (!$env->quote_balance = $this->getTotalBalance($env->quote_currency)) {
            Log::info($env->error = 'No '.$env->quote_currency.' balance on '.$this->getName());
        }
        return true;
    }


    public function tradeGetPosition(): bool
    {
        $env = $this->trade_environment;
        $env->current_position = $env->balance - $env->quote_balance / $env->price;
        return true;
    }


    protected function tradeSetTarget(): bool
    {
        $env = $this->trade_environment;
        $neutral_balance = ($this->isFutures($env->symbol) || $this->isSwap($env->symbol))
            ? 0
            : ($env->balance + $env->quote_balance / ($env->price ?? 1)) / 2; // spot
        Log::debug('neutral balance: '.$neutral_balance);
        if ('neutral' === $env->signal) {
            $env->target_balance = $neutral_balance;
            Log::debug($this->getName().' Target balance is '.$env->target_balance.' because signal is neutral');
        } else {
            if ($env->trade && $env->trade->signal_position) {
                $env->target_position = $env->trade->signal_position;
                Log::debug($this->getName().' Target set from trade', $env->target_position);
                return true;
            } else {
                if ($this->isFutures($env->symbol) || $this->isSwap($env->symbol)) {
                    $env->target_balance = $env->balance * $env->position_size / 100;
                    if ('short' === $env->signal) {
                        $env->target_balance = 0 - $env->target_balance;
                    }
                } else {
                    // spot
                    $change = $neutral_balance * $env->position_size / 100;
                    if ('short' === $env->signal) {
                        $change = 0 - $change;
                    }
                    $env->target_balance = $neutral_balance + $change;
                }
                Log::debug($this->getName().' Target calculated from balance', $env->target_balance);
            }
        }
        if (!$this->tradeCalculateTarget()) {
            $env->error = 'tradeCalculateTarget failed';
            return false;
        }
        return true;
    }


    protected function tradeCalculateTarget(): bool
    {
        $env = $this->trade_environment;
        if ($this->isFutures($env->symbol) || $this->isSwap($env->symbol)) {
            Log::debug($this->getName().' '.$env->symbol.' detected as futures or swap');
            $env->target_position = floor($env->target_balance * $env->price / $env->unit_value / $env->leverage);
        } else {
            // spot
            Log::debug($this->getName().' '.$env->symbol.' detected as spot');
            $env->target_position = $env->target_balance / $env->unit_value / $env->leverage;
        }
        Log::debug($this->getName().' calculated target position', $env->target_position);
        return true;
    }


    protected function tradeSetNewPosition(): bool
    {
        $env = $this->trade_environment;
        $env->new_position = $env->target_position - $env->current_position;
        //dump($env->target_position, $env->current_position, $env->new_position);
        return true;
    }


    protected function tradeTransactionNeccessary(): bool
    {
        $env = $this->trade_environment;
        if (!$env->new_position) {
            Log::info($env->error = 'Nothing to buy or sell', $env->symbol);
            return false;
        }
        if (abs($env->new_position) < abs($env->current_position / 100)) {
            Log::info($env->error = 'Less than 1% to change, aborting', $env->symbol, $env->current_position, $env->new_position);
            return false;
        }
        return true;
    }


    protected function tradePrepareTransaction(): bool
    {
        $env = $this->trade_environment;
        $env->side = 0 < $env->new_position ? 'buy' : 'sell';
        $env->new_position = abs($env->new_position);
        $env->order_type = $this->getUserOption('order_type');
        if ('market' === $env->order_type) {
            $env->price = null;
        } else {
            if ('limit_best' === $env->order_type) {
                $best_side = 'buy' === $env->side ? 'ask' : 'bid';
                if ($best_price = $this->getBestPrice($env->symbol, $best_side)) {
                    Log::debug($this->getName().' Best price', $best_price);
                    $env->price = $best_price;
                } else {
                    Log::error($this->getName().' Could not get best price', $env->symbol, $env->side);
                }
            }
            $env->order_type = 'limit';
            $env->price = $this->formatNumber($env->price, $env->symbol, 'price');
        }
        $env->new_position = $this->formatNumber($env->new_position, $env->symbol, 'amount');
        return true;
    }


    protected function tradeExecuteTransaction(): bool
    {
        $env = $this->trade_environment;
        Log::info($this->getName().'::createOrder()', $env->symbol, $env->order_type, $env->side, $env->new_position, $env->price);
        //dd($env);
        $env->order = null;
        try {
            $env->order = $this->ccxt()->createOrder($env->symbol, $env->order_type, $env->side, $env->new_position, $env->price);
        } catch (\Exception $e) {
            Log::error($this->getName().' Could not createOrder()', $e->getMessage());
        }
        return true;
    }


    protected function tradeSaveTransaction(): bool
    {
        $env = $this->trade_environment;
        $env->order_id = strval($env->order['id'] ?? null);
        $env->order['filled'] = $env->order['filled'] ?? 0 ? floatval($env->order['filled']) : 0;
        $trade = Trade::firstOrNew(['remote_id' => $env->order_id]);
        $trade->time                = time();
        $trade->remote_id           = $env->order_id;
        $trade->exchange_id         = $this->getId();
        $trade->symbol_id           = $env->symbol_id;
        $trade->user_id             = $env->user_id;
        $trade->bot_id              = $this->getParam('bot_id');
        $trade->amount_ordered      = $env->new_position;
        $trade->amount_filled       = $env->order['filled'] ?? null;
        $trade->price               = floatval($env->price);
        $trade->cost                = $env->order['cost'] ?? 0;
        $trade->action              = $env->side;
        $trade->type                = $env->order_type;
        $trade->fee                 = $env->order['fee']['cost'] ?? 0;
        $trade->fee_currency        = $env->order['fee']['currency'] ?? '';
        //$trade->status              = $order['status'] ?? '';
        $trade->status              = 'open';
        $trade->leverage            = $env->leverage;
        $trade->contract            = '';
        $trade->signal_time         = $env->signal_time;
        $trade->signal_position     = $env->target_position;
        $trade->open_balance        = $env->balance;
        $trade->save();
        return true;
    }


    public function takePosition(
        string $symbol,
        array $signal
    )
    {
        $this->trade_environment = (object)[
            'error' => null,
            'symbol' => $symbol,
            'signal' => $signal,
        ];
        $env = $this->trade_environment;

        foreach ([
            'ExtractSignal',
            'SetupEnvironment',
            'GetCurrency',
            'GetBalance',
            'GetPosition',
            'SetTarget',
            'SetNewPosition',
            'TransactionNeccessary',
            'PrepareTransaction',
            'ExecuteTransaction',
            'SaveTransaction'
        ] as $task) {
            if (!$this->{'trade'.$task}()) {
                //throw new \Exception($task.' returned false'.($env->error ? ': '.$env->error : ''));
                Log::info($task.' returned false', $this->getName(), $env->error);
            }
        }
        return $this;
    }


    public function getBestPrice(string $symbol, string $side): float
    {
        $nil = 0.0;
        if (!in_array($side, ['bid', 'ask'])) {
            Log::error('invalid side', $side);
            return $nil;
        }
        try {
            $book = $this->ccxt()->fetchOrderBook($symbol);
            assert(is_array($book));
        } catch (\Exception $e) {
            Log::info('fetchOrderBook failed', $this->getName(), $symbol, $e->getMessage());
            return $nil;
        }
        Log::debug('best prices: ['.reset($book['bids'])[0].' <--bid  ask--> '.reset($book['asks'])[0].']');
        $orders = is_array($entries = ($book[$side.'s'] ?? [])) ? $entries : [];
        $orders = reset($orders);
        return floatval($orders[0] ?? 0);
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


    public function getCurrency(string $symbol, string $type = 'base'): string
    {
        if (!in_array($type, ['base', 'quote'])) {
            throw new \Exception('invalid type: '.$type);
            return '';
        }
        if (!$market = $this->getMarket($symbol)) {
            throw new \Exception('could not get market');
            return '';
        }
        if (!$currency = ($market[$type] ?? null)) {
            throw new \Exception('could not get '.$type.' currency');
            return '';
        }
        return strval($currency);
    }


    public function fetchBalance(bool $use_cache = true): array
    {
        $cache_key = 'balance';
        if ($use_cache
            && ($cached = $this->cached($cache_key))
            && is_array($cached)) {
            return $cached;
        }
        try {
            //$this->ccxt()->loadMarkets();
            //$this->setCCXTProperty('verbose', true);
            $balance = $this->ccxt()->fetchBalance();
        } catch (\Exception $e) {
            Log::info('fetchBalance() failed', $e->getMessage());
            return [];
        }
        $this->cache($cache_key, $balance = is_array($balance) ? $balance : []);
        return $balance;
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


    public function getUnitValue(string $symbol): float
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


    public function marketType(string $symbol): string
    {
        $market = $this->getMarket($symbol);
        return isset($market['type']) ? strval($market['type']) : '';
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
        $key = 'fee_'.$this->getParam('ccxt_id').'_'.$symbol.'_'.$type;
        if ($cached = $this->cached($key)) {
            return $cached;
        }
        if ($cached = $this->pCached($key, null, 3600 * 24)) {
            $this->cache($key, $cached);
            return $cached;
        }
        $this->pCache($key, $val = floatval($this->getMarketPropery($symbol, $type)));
        $this->cache($key, $val);
        return $val;
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
        $since = ($since = $this->getLastClosedTradeTime())
            ? $since + 1
            : time() - 3600 * 24 * 30; // TODO put this in config
        $since *= 1000;
        $orders = [];
        try {
            //$this->ccxt()->loadMarkets();
            //$this->setCCXTProperty('verbose', true);
            $orders = $this->ccxt()->fetchClosedOrders(null, $since);
            //$this->setCCXTProperty('verbose', false);
            assert(is_array($orders));
        } catch (\Exception $e) {
            Log::error('could not fetch closed orders', $this->getName(), $e->getMessage());
        }
        if (!is_array($orders) || ! $count = count($orders)) {
            return $this;
        }
        Log::info('Saving '.$count.' order(s)', $this->getName(), $symbol);
        $leverage = $this->getUserOption('leverage', 1);
        foreach ($orders as $order) {
            $time = intval(intval($order['timestamp'] ?? 0) / 1000);
            $trade = Trade::firstOrNew(['remote_id' => $order['id'] ?? '']);
            $trade->time            = $time;
            $trade->remote_id       = $order['id'] ?? '';
            $trade->exchange_id     = $this->getId();
            $trade->symbol_id       = $this->getSymbolId(strval($order['symbol'] ?? ''));
            $trade->user_id         = $user_id;
            $trade->bot_id          = $bot_id;
            $trade->amount_ordered  = $order['amount'] ?? 0;
            $trade->amount_filled   = $order['filled'] ?? 0;
            $trade->price           = $order['price'] ?? 0;
            $trade->cost            = $order['cost'] ?? 0;
            $trade->action          = $order['side'] ?? '';
            $trade->type            = $order['type'] ?? '';
            $trade->fee             = $order['fee']['cost'] ?? 0;
            $trade->fee_currency    = $order['fee']['currency'] ?? '';
            $trade->status          = $order['status'] ?? '';
            $trade->leverage        = $leverage;
            $trade->contract        = '';
            $trade->close_balance   = $this->getTotalBalance($this->getCurrency($symbol));
            $trade->save();
        }
        return $this;
    }
}
