<?php

namespace GTrader;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

abstract class Exchange extends Base
{
    use HasCache, HasStatCache;

    abstract public function getTicker(string $symbol);
    abstract public function getCandles(
        string $symbol,
        int $resolution,
        int $since = 0,
        int $size = 0
    );
    abstract public function takePosition(
        string $symbol,
        string $signal,
        float $price,
        int $bot_id = null
    );
    abstract public function cancelUnfilledOrders(string $symbol, int $before_timestamp);
    abstract public function saveFilledOrders(string $symbol, int $bot_id = null);


    public static function make(string $class = null, array $params = [])
    {
        // hack for CCXTWrapper
        $ccxtwrapper = 'CCXTWrapper';
        if ($ccxtwrapper === substr($class, 0, 11)) {
            if ($ccxt_id = strstr($class, '_')) {
                if (strlen($ccxt_id = substr($ccxt_id, 1))) {
                    $params['ccxt_id'] = $ccxt_id;
                    Log::debug('making '.$ccxtwrapper.' with ccxt_id='.$ccxt_id);
                    return parent::make($ccxtwrapper, $params);
                }
            }
        }
        return parent::make($class, $params);
    }


    /**
     * Get the options configured by the user.
     *
     * @return array
     */
    public function getUserOptions()
    {
        if ($options = $this->cached('user_options')) {
            return $options;
        }
        if (!($user_id = $this->getParam('user_id'))) {
            throw new \Exception('cannot getUserOptions() without user_id');
        }
        $config = UserExchangeConfig::select('options')
            ->where('user_id', $user_id)
            ->where('exchange_id', $this->getId())
            ->first();
        if (null === $config) {
            return null;
        }
        $this->cache('user_options', $config->options);
        return $config->options;
    }


    /**
     * Get an option configured by the user.
     *
     * @return mixed
     */
    public function getUserOption(string $option)
    {
        $options = $this->getUserOptions() ?? [];
        return $options[$option] ?? null;
    }


    public function updateUserOptions(UserExchangeConfig $config, array $new_options)
    {
        $options = $config->options;
        foreach ($this->getParam('user_options') as $key => $default) {
            Log::debug('updating '.$key.' to ', $new_options[$key] ?? $default);
            $options[$key] = $new_options[$key] ?? $default;
        }
        $config->options = $options;
        $config->save();
        $this->unCache('user_options');
        return $this;
    }


    public static function getDefault(string $param)
    {
        $exchange = Exchange::singleton();
        if ('exchange' === $param) {
            return $exchange->getParam('local_name');
        }
        if ('symbol' === $param) {
            $symbols = $exchange->getParam('symbols');
            $first_symbol = reset($symbols);
            return $first_symbol['local_name'];
        }
        if ('resolution' === $param) {
            $symbols = $exchange->getParam('symbols');
            $first_symbol = reset($symbols);
            $resolutions = $first_symbol['resolutions'];
            if (isset($resolutions[3600])) {// prefer 1-hour
                return 3600;
            }
            reset($resolutions);
            return key($resolutions); // return first res
        }
        return null;
    }


    public function getId()
    {
        return self::getIdByName($this->getShortClass());
    }


    public static function getNameById(int $id)
    {
        if ($name = static::statCached('id_'.$id)) {
            return $name;
        }
        $query = DB::table('exchanges')
                    ->select('name')
                    ->where('id', $id)
                    ->first();
        if (is_object($query)) {
            static::statCache('id_'.$id, $query->name);
            return $query->name;
        }
        return null;
    }


    public static function getIdByName(string $name)
    {
        if ($id = static::statCached('name_'.$name)) {
            return $id;
        }
        $id = null;
        $query = DB::table('exchanges')
            ->select('id')
            ->where('name', $name)
            ->first();
        if (is_object($query)) {
            $id = $query->id;
        } else {
            $id = DB::table('exchanges')->insertGetId([
                'name' => $name,
            ]);
        }
        if ($id) {
            static::statCache('name_'.$name, $id);
            return $id;
        }
        return null;
    }


    public static function getSymbolIdByExchangeSymbolName(string $exchange_name, string $symbol_name)
    {
        if ($id = static::statCached('exchange_symbol_'.$exchange_name.'_'.$symbol_name)) {
            return $id;
        }
        $query = DB::table('symbols')
                    ->select('symbols.id')
                    ->join('exchanges', function ($join) use ($exchange_name) {
                        $join->on('exchanges.id', '=', 'symbols.exchange_id')
                            ->where('exchanges.name', $exchange_name);
                    })
                    ->where('symbols.name', $symbol_name)
                    ->first();
        if (is_object($query)) {
            static::statCache('exchange_symbol_'.$exchange_name.'_'.$symbol_name, $query->id);
            return $query->id;
        }
        return null;
    }


    public function getSymbolIdByRemoteName(string $remote_name)
    {
        foreach ($this->getParam('symbols') as $symbol) {
            if ($symbol['remote_name'] === $remote_name) {
                return self::getSymbolIdByExchangeSymbolName(
                    $this->getParam('local_name'),
                    $symbol['local_name']
                );
            }
        }
    }


    public static function getSymbolNameById(int $id)
    {
        if ($name = static::statCached('symbol_'.$id)) {
            return $name;
        }
        $query = DB::table('symbols')
            ->select('name')
            ->where('id', $id)
            ->first();
        if (is_object($query)) {
            static::statCache('symbol_'.$id, $query->name);
            return $query->name;
        }
        return null;
    }


    public function getSupported(array $options = []): array
    {
        // by default an exchange class supports only a single real-world exchange
        return [$this];
    }


    public function getSymbols(array $options = []): array
    {
        return $this->getParam('symbols', []);
    }


    public function getResolutions(string $symbol_id = '', array $options = []): array
    {
        if (!count($symbols = $this->getSymbols())) {
            Log::error('no symbols for '.$this->getShortClass());
            return [];
        }
        if (strlen($symbol_id)) {
            if (!isset($symbols[$symbol_id])) {
                Log::error('symbol not found: '.$symbol_id);
                return [];
            }
            return $symbols[$symbol_id]['resolutions'];
        }
        // return the resolutions of the first symbol
        return $symbols[0]['resolutions'];
    }


    public static function getAvailable(array $options = []): array
    {
        $exchanges = [];
        $default_exchange = Exchange::singleton();
        foreach ($default_exchange->getParam('available_exchanges') as $class) {
            $exchange = Exchange::make($class);
            foreach ($exchange->getSupported($options) as $supported) {
                $exchanges[] = $supported;
            }
        }
        return $exchanges;
    }


    public static function getESR(): array
    {
        $esr = [];
        foreach (static::getAvailable() as $exchange) {
            $exo = new \stdClass();
            $exo->name = $exchange->getParam('local_name');
            $exo->short_name = $exchange->getParam('short_name');
            $exo->symbols = [];

            foreach ($exchange->getParam('symbols', []) as $symbol) {
                $symo = new \stdClass();
                $symo->name = $symbol['local_name'];
                $symo->short_name = $symbol['short_name'];
                $symo->resolutions = $symbol['resolutions'];
                $exo->symbols[] = $symo;
            }
            $esr[] = $exo;
        }
        return $esr;
    }


    public static function getESRReadonly(
        string $exchange,
        string $symbol,
        int $resolution
    ) {
        //Log::info('Exchange::getESRReadonly('.$exchange.', '.$symbol.', '.$resolution.')');
        //return '';
        try {
            $exchange = self::make($exchange);
        } catch (\Exception $e) {
            return null;
        }
        if (!($symbol = $exchange->getParam('symbols')[$symbol])) {
            return null;
        }
        return $exchange->getParam('short_name').' / '.
            $symbol['short_name'].' / '.
            $symbol['resolutions'][$resolution];
    }


    public static function getESRSelector(string $name)
    {
        return view('ESRSelector', ['name' => $name]);
    }


    public static function getList(array $options = [])
    {
        return view('Exchanges/List', ['exchanges' => static::getAvailable($options)]);
    }


    public function getInfo()
    {
        return view('Exchanges/Info', ['exchange' => $this]);
    }


    public function getSymbol(string $symbol_id = ''): array
    {
        if (!strlen($symbol_id)) {
            Log::error('need symbol in '.$this->getShortClass());
            return [];
        }
        $symbols = $this->getSymbols();
        if (!isset($symbols[$symbol_id])) {
            Log::error('symbol not set in '.$this->getShortClass(), $symbol_id);
            return [];
        }
        if (!is_array($symbols[$symbol_id])) {
            Log::error('symbol not an array in '.$this->getShortClass(), $symbol_id);
            return [];
        }
        return $symbols[$symbol_id];
    }


    public function getSymbolName(string $symbol_id): string
    {
        $symbol = $this->getSymbol($symbol_id);
        if (!isset($symbol['local_name'])) {
            return $symbol_id;
        }
        return $symbol['local_name'];
    }


    public function getName()
    {
        return $this->getParam('local_name');
    }


    public function form(array $options = [])
    {
        $this->setParam('user_id', Auth::user()->id);
        return view('Exchanges/Form', [
            'exchange' => $this,
        ]);
    }
}
