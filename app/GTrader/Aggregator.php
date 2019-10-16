<?php

namespace GTrader;

use Illuminate\Support\Facades\DB;

/**
 * Fetches new candles from all available exchanes and stores them in the DB
 */
class Aggregator extends Base
{
    use Scheduled;

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

        if (!$this->tableExists()) {
            return $this;
        }

        echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' ';

        foreach ($this->getExchanges() as $exchange_class) {
            $exchange = $this->getExchange($exchange_class);
            $exchange_id = $exchange->getId();
            $symbols = $exchange->getSymbols(['get' => ['configured']]);
            if (!is_array($symbols)) {
                continue;
            }
            if (!count($symbols)) {
                continue;
            }
            $delay = $exchange->getParam('aggregator_delay', 0);
            echo PHP_EOL.$exchange->getName().': ';

            foreach ($symbols as $symbol_name => $symbol) {
                //dump($exchange->getName(), $symbols);
                if (!isset($symbol['resolutions'])) {
                    continue;
                }
                if (!is_array($symbol['resolutions'])) {
                    continue;
                }
                if (!isset($symbol['long_name'])) {
                    $symbol['long_name'] = $symbol_name;
                }
                $symbol_id = $exchange->getOrCreateSymbolId(
                    $symbol_name,
                    $symbol['long_name'],
                );

                echo $symbol_name.': ';

                foreach ($symbol['resolutions'] as $resolution => $res_name) {
                    $time = $this->getLastCandleTime(
                        $exchange->getParam('id'),
                        $symbol_id,
                        $resolution
                    );
                    $since = $time - $resolution;
                    $since = $since > 0 ? $since : 0;

                    echo $res_name.' ('.date('Y-m-d H:i', $since).'): ';
                    flush();

                    try {
                        $candles = $exchange->fetchCandles(
                            $symbol_name,
                            $resolution,
                            $since,
                            $exchange->getParam('aggregator_chunk_size', 1000)
                        );
                    } catch (\Exception $e) {
                        echo PHP_EOL.'Error: '.$e->getMessage();
                        Log::error($e->getMessage());
                    }

                    if (!is_array($candles)) {
                        echo '0, ';
                        continue;
                    }
                    if (!count($candles)) {
                        echo '0, ';
                        continue;
                    }
                    foreach ($candles as $candle) {
                        $candle->exchange_id = $exchange_id;
                        $candle->symbol_id = $symbol_id;
                        $candle->resolution = $resolution;
                        try {
                            Series::saveCandle($candle);
                        } catch (\Exception $e) {
                            echo PHP_EOL.'Error: '.$e->getMessage();
                            Log::error($e->getMessage());
                        }
                    }
                    echo count($candles).', ';
                    usleep($delay);
                }
            }
        }
        echo 'All done.'.PHP_EOL;

        Lock::release($lock);

        return $this;
    }


    /**
     * Deletes candles older than the # of days specified in config: exchange.delete_candle_age
     * @return $this
     */
    public function deleteOld()
    {
        if (!$this->tableExists()) {
            return $this;
        }
        foreach ($this->getExchanges() as $exchange_class) {
            $exchange = $this->getExchange($exchange_class);
            dump($exchange->getName().': '.
                $exchange->getParam('delete_candle_age')
            );
        }
        return $this;
    }


    /**
     * Checks if the DB is ready and exchanges table exists
     * @return bool
     */
    protected function tableExists()
    {
        try {
            if (!count(DB::select(DB::raw('show tables like "exchanges"')))) {
                Log::error('Exchanges table does not (yet) exist in the database.');
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Database is not ready (yet).');
            return false;
        }
        return true;
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

    protected function getExchanges()
    {
        return Exchange::getAvailable([
            'get' => ['configured']
        ]);
    }

    /**
     * returns the Exhange object
     * @param  string $class Class name
     * @return Exchange
     */
    protected function getExchange($exchange)
    {
        if (!is_object($exchange)) {
            $exchange = Exchange::make($exchange);
        }
        if (!$exchange_id = $exchange->getOrAddIdByName(
            $exchange->getName(),
            $exchange->getLongName()
        )) {
            throw new \Exception('could not get id');
        }
        $exchange->setParam('id', $exchange_id);
        return $exchange;
    }
}
