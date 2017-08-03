<?php

namespace GTrader;

use Illuminate\Support\Facades\DB;

abstract class Exchange
{
    use Skeleton;

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


    /**
     * Get the options configured by the user.
     *
     * @return array
     */
    public function getUserOptions()
    {
        if (!($user_id = $this->getParam('user_id'))) {
            throw new \Exception('cannot getUserOptions() without user_id');
        }
        if ($options = $this->getParam('user_options_cached')) {
            return $options;
        }
        $config = UserExchangeConfig::select('options')
                        ->where('user_id', $user_id)
                        ->where('exchange_id', $this->getId())
                        ->first();
        if (null === $config) {
            error_log('Exchange has not yet been configured by the user.');
            return [];
        }
        $this->setParam('user_options_cached', $config->options);
        return $config->options;
    }


    /**
     * Get an option configured by the user.
     *
     * @return mixed
     */
    public function getUserOption(string $option)
    {
        $options = $this->getUserOptions();

        if (!isset($options[$option])) {
            throw new \Exception($option.' not set');
        }
        if (is_null($options[$option])) {
            throw new \Exception($option.' is null');
        }
        return $options[$option];
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
        $query = DB::table('exchanges')
                    ->select('name')
                    ->where('id', $id)
                    ->first();
        if (is_object($query)) {
            return $query->name;
        }
        return null;
    }


    public static function getIdByName(string $name)
    {
        $query = DB::table('exchanges')
            ->select('id')
            ->where('name', $name)
            ->first();
        if (is_object($query)) {
            return $query->id;
        }
        return null;
    }

    public static function getSymbolIdByExchangeSymbolName(string $exchange_name, string $symbol_name)
    {
        $query = DB::table('symbols')
                    ->select('symbols.id')
                    ->join('exchanges', function ($join) use ($exchange_name) {
                        $join->on('exchanges.id', '=', 'symbols.exchange_id')
                            ->where('exchanges.name', $exchange_name);
                    })
                    ->where('symbols.name', $symbol_name)
                    ->first();
        if (is_object($query)) {
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
        $query = DB::table('symbols')
            ->select('name')
            ->where('id', $id)
            ->first();
        if (is_object($query)) {
            return $query->name;
        }
        return null;
    }


    public static function getESR()
    {
        $esr = [];
        $default_exchange = Exchange::singleton();
        foreach ($default_exchange->getParam('available_exchanges') as $class) {
            $exchange = Exchange::make($class);
            $exo = new \stdClass();
            $exo->name = $exchange->getParam('local_name');
            $exo->long_name = $exchange->getParam('long_name');
            $exo->short_name = $exchange->getParam('short_name');
            $exo->symbols = [];

            foreach ($exchange->getParam('symbols') as $symbol) {
                $symo = new \stdClass();
                $symo->name = $symbol['local_name'];
                $symo->long_name = $symbol['long_name'];
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
        //error_log('Exchange::getESRReadonly('.$exchange.', '.$symbol.', '.$resolution.')');
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


    public static function getList()
    {
        $default = self::singleton();
        $exchanges = [];
        foreach ($default->getParam('available_exchanges') as $class) {
            $exchanges[] = self::make($class);
        }
        return view('Exchanges/List', ['exchanges' => $exchanges]);
    }
}
