<?php

namespace GTrader;

use Illuminate\Support\Facades\DB;

/**
 * Fetches new candles from all available exchanes and stores them in the DB
 */
class Aggregator
{
    use Skeleton, Scheduled;

    /**
     * Main method
     * @return $this
     */
    public function aggregate()
    {
        if (!$this->scheduleEnabled()) {
            return $this;
        }
        //ignore_user_abort(true);

        $lock = str_replace('::', '_', str_replace('\\', '_', __METHOD__));
        if (!Lock::obtain($lock)) {
            Log::info('Another aggregator process is running.');
            return $this;
        }

        try {
            if (!count(DB::select(DB::raw('show tables like "exchanges"')))) {
                Log::error('Exchanges table does not (yet) exist in the database.');
                return $this;
            }
        } catch (\Exception $e) {
            Log::error('Database is not ready (yet).');
            return $this;
        }

        echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' ';

        foreach ($this->getExchanges() as $exchange_class) {
            $exchange = $this->getExchange($exchange_class);
            $symbols = $exchange->getParam('symbols');
            if (!is_array($symbols)) {
                continue;
            }
            echo $exchange->getParam('short_name').': ';

            foreach ($symbols as $symbol_local => $symbol) {
                if (!is_array($symbol['resolutions'])) {
                    continue;
                }
                $symbol_id = $this->getSymbolId(
                    $exchange->getParam('id'),
                    $symbol_local,
                    $symbol['long_name']
                );

                echo $symbol_local.': ';

                foreach ($symbol['resolutions'] as $resolution => $res_name) {
                    $time = $this->getLastCandleTime(
                        $exchange->getParam('id'),
                        $symbol_id,
                        $resolution
                    );

                    echo $res_name.': ';
                    flush();

                    $candles = $exchange->getCandles(
                        $symbol_local,
                        $resolution,
                        $time - $resolution,
                        100000
                    );

                    if (!is_array($candles)) {
                        echo '0, ';
                        continue;
                    }
                    if (!count($candles)) {
                        echo '0, ';
                        continue;
                    }
                    foreach ($candles as $candle) {
                        $candle->exchange_id = $exchange->getParam('id');
                        $candle->symbol_id = $symbol_id;
                        $candle->resolution = $resolution;
                        Series::saveCandle($candle);
                    }
                    echo count($candles).', ';
                }
            }
        }
        echo 'all done.'.PHP_EOL;

        Lock::release($lock);

        return $this;
    }

    /**
     * Get last candle time
     * @param  int    $exchange_id
     * @param  int    $symbol_id
     * @param  int    $resolution
     * @return int
     */
    protected function getLastCandleTime(
        int $exchange_id,
        int $symbol_id,
        int $resolution
    ) {
        $time = DB::table('candles')
            ->select('time')
            ->where('exchange_id', $exchange_id)
            ->where('symbol_id', $symbol_id)
            ->where('resolution', $resolution)
            ->latest('time')
            ->first();
        return is_object($time) ? $time->time : 0;
    }

    /**
     * Returns available exchange class names
     * @return array
     */
    protected function getExchanges()
    {
        $default_exchange = Exchange::make();
        return $default_exchange->getParam('available_exchanges');
    }

    /**
     * returns the Exhange object
     * @param  string $class Class name
     * @return Exchange
     */
    protected function getExchange(string $class)
    {
        $exchange = Exchange::make($class);
        $exchange_id = null;
        $o = DB::table('exchanges')
            ->select('id')
            ->where('name', $exchange->getParam('local_name'))
            ->first();
        if (is_object($o)) {
            $exchange_id = $o->id;
        }
        if (!$exchange_id) {
            $exchange_id = DB::table('exchanges')
                ->insertGetId([
                    'name' => $exchange->getParam('local_name'),
                    'long_name' => $exchange->getParam('long_name')
                ]);
        }
        $exchange->setParam('id', $exchange_id);
        return $exchange;
    }

    /**
     * Returns symbol ID
     * @param  int    $exchange_id
     * @param  string $local_name
     * @param  string $long_name
     * @return int
     */
    protected function getSymbolId(
        int $exchange_id,
        string $local_name,
        string $long_name
    ) {
        $symbol_id = null;
        $o = DB::table('symbols')
            ->select('id')
            ->where('name', $local_name)
            ->where('exchange_id', $exchange_id)
            ->first();
        if (is_object($o)) {
            $symbol_id = $o->id;
        }
        if (!$symbol_id) {
            $symbol_id = DB::table('symbols')
                ->insertGetId([
                    'name' => $local_name,
                    'exchange_id' => $exchange_id,
                    'long_name' => $long_name,
                ]);
        }
        return $symbol_id;
    }
}
