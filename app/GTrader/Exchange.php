<?php

Namespace GTrader;

use Illuminate\Support\Facades\DB;
use GTrader\Exchange;

abstract class Exchange
{
    use Skeleton;

    abstract public function getTicker(array $params = []);
    abstract public function getCandles(array $params = []);
    abstract public function takePosition(string $position);


    public static function getDefault(string $param)
    {
        $exchange = Exchange::singleton();
        if ('exchange' === $param)
            return $exchange->getParam('local_name');
        if ('symbol' === $param)
        {
            $symbols = $exchange->getParam('symbols');
            $first_symbol = reset($symbols);
            return $first_symbol['local_name'];
        }
        if ('resolution' === $param)
        {
            $symbols = $exchange->getParam('symbols');
            $first_symbol = reset($symbols);
            $resolutions = $first_symbol['resolutions'];
            if (isset($resolutions[3600])) // prefer 1-hour
                return 3600;
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
        if (is_object($query))
            return $query->name;
        return null;
    }


    public static function getIdByName(string $name)
    {
        $query = DB::table('exchanges')
                    ->select('id')
                    ->where('name', $name)
                    ->first();
        if (is_object($query))
            return $query->id;
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
        if (is_object($query))
            return $query->id;
        return null;
    }


    public static function getSymbolNameById(int $id)
    {
        $query = DB::table('symbols')
                    ->select('name')
                    ->where('id', $id)
                    ->first();
        if (is_object($query))
            return $query->name;
        return null;
    }


    public static function getESR()
    {
        $esr = [];
        $default_exchange = Exchange::singleton();
        foreach ($default_exchange->getParam('available_exchanges') as $class)
        {
            $exchange = Exchange::make($class);
            $exo = new \stdClass();
            $exo->name = $exchange->getParam('local_name');
            $exo->long_name = $exchange->getParam('long_name');
            $exo->symbols = [];

            foreach ($exchange->getParam('symbols') as $symbol)
            {
                $symo = new \stdClass();
                $symo->name = $symbol['local_name'];
                $symo->long_name = $symbol['long_name'];
                $symo->resolutions = $symbol['resolutions'];
                $exo->symbols[] = $symo;
            }
            $esr[] = $exo;
        }
        return $esr;
    }

    public static function getESRReadonly(  string $exchange,
                                            string $symbol,
                                            int $resolution)
    {
        //error_log('Exchange::getESRReadonly('.$exchange.', '.$symbol.', '.$resolution.')');
        //return '';
        try {
            $exchange = self::make($exchange);
        } catch (\Exception $e) {
            return null;
        }
        if (!($symbol = $exchange->getParam('symbols')[$symbol]))
            return null;
        return $exchange->getParam('long_name').' / '.
                $symbol['long_name'].' / '.
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
        foreach ($default->getParam('available_exchanges') as $class)
            $exchanges[] = self::make($class);
        return view('Exchange/List', ['exchanges' => $exchanges]);
    }
}











