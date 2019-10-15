<?php

namespace GTrader;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;

abstract class Exchange extends Base
{
    use HasCache, HasStatCache;

    abstract public function getTicker(string $symbol);
    abstract public function fetchCandles(
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
        $ccxt = 'CCXT';
        if ($ccxt === substr($class, 0, 4)) {
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
    public function getUserOptions(array $options = [])
    {
        $user_id = Arr::get($options, 'user_id') ??
            $this->getParam('user_id') ??
            Auth::id();
        //Log::debug('checking user config opts for ', $this->getName(), $user_id);
        if (!$user_id) {
            return [];
        }
        $cache_key = 'user_options';
        if ($options = $this->cached($cache_key)) {
            return $options;
        }
        $options = UserExchangeConfig::select('options')
            ->where('user_id', $user_id)
            ->where('exchange_id', $this->getId())
            ->value('options');
        if (!is_array($options)) {
            Log::debug('no user config opts for ', $this->getName(), $user_id);
            return [];
        }
        $this->cache($cache_key, $options);
        return $options;
    }


    /**
     * Get an option configured by the user.
     *
     * @return mixed
     */
    public function getUserOption(string $option, $default = null)
    {
        $options = $this->getUserOptions() ?? [];
        return $options[$option] ?? $default;
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


    public function handleSaveRequest(Request $request, UserExchangeConfig $config)
    {
        $this->updateUserOptions($config, $request->options ?? []);
        return $this;
    }


    public static function getDefault(string $param)
    {
        $exchange = Exchange::singleton();
        if ('exchange' === $param) {
            return $exchange->getName();
        }
        if ('symbol' === $param) {
            $symbols = $exchange->getSymbols();
            $first_symbol = reset($symbols);
            return $first_symbol['name'];
        }
        if ('resolution' === $param) {
            $symbols = $exchange->getSymbols();
            $first_symbol = reset($symbols);
            if (!$first_symbol['name']) {
                Log::error('no first symbol for '.$exchange->getName());
                return null;
            }
            if (!count($resolutions = $exchange->getResolutions($first_symbol['name']))) {
                Log::error('no resolutions for '.$exchange->getName());
                return null;
            }
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
        return self::getOrAddIdByName(
            $this->getParam('name'),
            $this->getParam('long_name')
        );
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


    public static function getOrAddIdByName(string $name, string $long_name_if_new = '')
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
        } elseif (strlen($long_name_if_new)) {
            $id = DB::table('exchanges')->insertGetId([
                'name' => $name,
                'long_name' => $long_name_if_new,
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
        return count($symbol = self::getSymbolByExchangeSymbolName(
            $exchange_name,
            $symbol_name
        )) ? $symbol['id'] : null;
    }


    public static function getSymbolLongNameByExchangeSymbolName(string $exchange_name, string $symbol_name)
    {
        return count($symbol = self::getSymbolByExchangeSymbolName(
            $exchange_name,
            $symbol_name
        )) ? $symbol['long_name'] : $symbol_name;
    }


    public static function getSymbolByExchangeSymbolName(string $exchange_name, string $symbol_name)
    {
        $cache_key = 'exchange_symbol_'.$exchange_name.'_'.$symbol_name;
        if ($symbol = static::statCached($cache_key)) {
            return $symbol;
        }
        $query = DB::table('symbols')
            ->select(['symbols.id', 'symbols.long_name'])
            ->join('exchanges', function ($join) use ($exchange_name) {
                $join->on('exchanges.id', '=', 'symbols.exchange_id')
                    ->where('exchanges.name', $exchange_name);
            })
            ->where('symbols.name', $symbol_name)
            ->first();
        if (is_object($query)) {
            $return = [
                'id' => $query->id,
                'name' => $symbol_name,
                'long_name' => $query->long_name,
            ];
            static::statCache($cache_key, $return);
            return $return;
        }
        return [];
    }


    public function getSymbolIdByRemoteName(string $remote_name)
    {
        foreach ($this->getParam('symbols') as $symbol) {
            if ($symbol['remote_name'] === $remote_name) {
                return self::getSymbolIdByExchangeSymbolName(
                    $this->getName(),
                    $symbol['name']
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
        $get = Arr::get($options, 'get', ['all']);
        $all = in_array('all', $get);
        $configured = in_array('configured', $get);
        $user_id = Arr::get($options, 'user_id');

        if ($all) {
            return $this->getAllSymbols();
        }
        if ($configured) {
            return $this->getConfiguredSymbols($options);
        }
        return $this->getAllSymbols();
    }


    protected function getAllSymbols(): array
    {
        return $this->getParam('symbols', []);
    }


    protected function getConfiguredSymbols(array $options = []): array
    {
        $user_id = Arr::get($options, 'user_id');
        $config = UserExchangeConfig::select('options');
        if ($user_id) {
            $config->where('user_id', $user_id);
        }
        $configs = $config->where('exchange_id', $this->getId())->get();
        $configured_symbols = [];
        foreach ($configs as $config) {
            if (!isset($config->options)
                || !is_array($config->options)
                || !isset($config->options['symbols'])
                || !is_array($config->options['symbols'])) {
                continue;
            }
            foreach ($config->options['symbols'] as $cosk => $cosv) {
                if (!is_array($cosv)
                    || !isset($cosv['resolutions'])
                    || !is_array($cosv['resolutions'])) {
                    continue;
                }
                if (!isset($configured_symbols[$cosk])) {
                    $configured_symbols[$cosk] = [];
                }
                $new_resolutions = [];
                foreach ($cosv['resolutions'] as $res) {
                    $new_resolutions[$res] = self::resolutionName($res);
                }
                $configured_symbols[$cosk]['resolutions'] = array_replace(
                    $configured_symbols[$cosk]['resolutions'] ?? [],
                    $new_resolutions
                );
            }
        }
        return $configured_symbols;
    }


    public function getResolutions(string $symbol = '', array $options = []): array
    {
        if (!count($symbols = $this->getSymbols($options))) {
            Log::error('no symbols for '.$this->getShortClass());
            return [];
        }
        if (strlen($symbol)) {
            if (!isset($symbols[$symbol])) {
                Log::error('symbol not found: '.$symbol);
                return [];
            }
            if (!isset($symbols[$symbol]['resolutions'])) {
                Log::error('resolutions not found for: '.$symbol);
                return [];
            }
            if (!is_array($symbols[$symbol]['resolutions'])) {
                Log::error('resolutions not an array for: '.$symbol);
                return [];
            }
            //Log::debug(count($symbols[$symbol]['resolutions']).' resolutions for '.$symbol);
            return $symbols[$symbol]['resolutions'];
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
        //Log::debug('getESR()');
        $options = ['get' => ['configured']];
        $esr = [];
        foreach (static::getAvailable($options) as $exchange) {
            $exo = new \stdClass();
            $exo->name = $exchange->getName();
            $exo->long_name = $exchange->getLongName();
            $exo->symbols = [];
            //Log::debug('getESR() '.$exchange->getName());

            foreach ($exchange->getSymbols($options) as $symbol_name => $symbol) {
                $resolutions =
                    $symbol['resolutions'] ?
                    is_array($symbol['resolutions']) ?
                        $symbol['resolutions'] : []
                    : [];
                $symo = new \stdClass();
                $symo->name = $symbol_name;
                $symo->long_name = self::getSymbolLongNameByExchangeSymbolName($exo->name, $symbol_name);
                //$symo->resolutions = $exchange->getResolutions($symbol_name, $options);
                $symo->resolutions = $resolutions;
                $exo->symbols[] = $symo;
                //Log::debug('getESR() '.$symbol_name.': '.count($symo->resolutions));
            }
            $esr[] = $exo;
        }
        return $esr;
    }


    public static function getESRReadonly(
        string $exchange_name,
        string $symbol_name,
        int $resolution
    ) {
        return $exchange_name.' / '.$symbol_name.' / '.$resolution;
        //Log::info('Exchange::getESRReadonly('.$exchange.', '.$symbol.', '.$resolution.')');
        //return '';
        /*
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
        */
    }


    public static function getESRSelector(string $name)
    {
        return view('ESRSelector', ['name' => $name]);
    }


    public static function getList(array $options = [])
    {
        $reload = Arr::get($options, 'reload');
        return view(
            'Exchanges/List',
            [
                'exchanges' => static::getAvailable($options),
                'reload' => $reload,
            ]
        );
    }


    public function getListItem()
    {
        return view('Exchanges/ListItem', ['exchange' => $this]);
    }


    public function getInfo()
    {
        return view('Exchanges/Info', ['exchange' => $this]);
    }


    public function getSymbol(string $symbol_name = ''): array
    {
        if (!strlen($symbol_name)) {
            Log::error('need symbol in '.$this->getShortClass());
            return [];
        }
        $symbols = $this->getSymbols();
        if (!isset($symbols[$symbol_name])) {
            Log::error('symbol not set in '.$this->getShortClass(), $symbol_name);
            return [];
        }
        if (!is_array($symbols[$symbol_name])) {
            Log::error('symbol not an array in '.$this->getShortClass(), $symbol_name);
            return [];
        }
        return $symbols[$symbol_name];
    }



    public static function resolutionName(int $resolution): string
    {
        $map = Exchange::singleton()->getParam('resolution_map');
        return array_search($resolution, $map) ?? strval($resolution);
    }


    public function getName()
    {
        return $this->getParam('name');
    }


    public function getLongName()
    {
        return $this->getParam('long_name');
    }


    public function form(array $options = [])
    {
        $this->setParam('user_id', Auth::id());
        return view('Exchanges/Form', [
            'exchange' => $this,
        ]);
    }


    /**
     * Returns or creates and returns symbol ID
     * @param  string $name
     * @param  string $long_name
     * @return int | null
     */
    public function getOrCreateSymbolId(
        string $name,
        string $long_name = ''
    ) {
        if (!strlen($name)) {
            Log::error('name is required');
            return null;
        }
        $exchange_id = $this->getId();
        $symbol_id = null;
        $o = DB::table('symbols')
            ->select('id')
            ->where('name', $name)
            ->where('exchange_id', $exchange_id)
            ->first();
        if (is_object($o)) {
            $symbol_id = $o->id;
        }
        if (!$symbol_id && strlen($long_name)) {
            $symbol_id = DB::table('symbols')->insertGetId([
                'name' => $name,
                'long_name' => $long_name,
                'exchange_id' => $exchange_id
            ]);
        }
        return $symbol_id;
    }
}
