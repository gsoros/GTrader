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
            if (!is_array($symbols) || !count($symbols)) {
                continue;
            }
            $chunk_size = $exchange->getParam('aggregator_chunk_size', 100);
            $delay = $exchange->getParam('aggregator_delay', 0);
            echo PHP_EOL.$exchange->getName().': ';

            foreach ($symbols as $symbol_name => $symbol) {
                //dump($exchange->getName(), $symbols);
                if (!isset($symbol['resolutions']) || !is_array($symbol['resolutions'])) {
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
                    $last = $this->getCandleTime(
                        $exchange_id,
                        $symbol_id,
                        $resolution,
                        'last'
                    );
                    $last = $last ? $last : time();
                    $since = $last - $resolution;
                    $since = $since > 0 ? $since : 0;

                    echo $res_name.' ('.date('Y-m-d H:i', $since).'): ';
                    flush();

                    $candles = $this->fetchCandles(
                        $exchange,
                        $symbol_name,
                        $resolution,
                        $since,
                        $chunk_size
                    );
                    $count = count($candles);
                    $this->saveCandles($candles, $exchange_id, $symbol_id, $resolution);

                    $epoch_key = 'epochs.'.str_replace('.', '_', $symbol_name);
                    $epoch = $exchange->getGlobalOption($epoch_key);
                    $first = $this->getCandleTime(
                        $exchange_id,
                        $symbol_id,
                        $resolution,
                        'first'
                    );
                    $ancient_count = 0;
                    if (!$epoch || $epoch < $first) {
                        usleep($delay);
                        $fetch_last = $first ? $first : time();
                        $ancient_candles = $this->fetchCandles(
                            $exchange,
                            $symbol_name,
                            $resolution,
                            $fetch_last - $chunk_size * $resolution,
                            $chunk_size
                        );
                        if ($ancient_count = count($ancient_candles)) {
                            echo $ancient_count;
                            $ancient_duplicates = 0;
                            foreach ($ancient_candles as $key => $candle) {
                                if (DB::table('candles')->where([
                                        ['time', $candle->time],
                                        ['exchange_id', $exchange_id],
                                        ['symbol_id', $symbol_id],
                                        ['resolution', $resolution],
                                    ])->exists()) {
                                    $ancient_duplicates++;
                                    unset($ancient_candles[$key]);
                                }
                            }
                            if ($ancient_duplicates) {
                                echo ' ['.$ancient_duplicates.']';
                                if ($ancient_duplicates === $ancient_count) {
                                    $exchange->setGlobalOption($epoch_key, $first);
                                }
                            }
                            $remaining = count($ancient_candles);
                            if ($first &&
                                    isset($ancient_candles[$remaining - 1]) &&
                                    ($ancient_candles[$remaining - 1]->time
                                        < ($first - $resolution))
                                ) {
                                Log::error('Gap detected at '.$first.
                                    ', chunk size of '.$chunk_size.
                                    ' might be too high for '.$exchange->getName()
                                );
                                echo '[GAP] ';
                            }
                            else {
                                echo '<->';
                                $this->saveCandles(array_reverse($ancient_candles),
                                    $exchange_id, $symbol_id, $resolution);
                            }
                        } elseif ($first) {
                            $exchange->setGlobalOption($epoch_key, $first);
                        }
                    }

                    if (!$count && !$ancient_count) {
                        echo '0, ';
                        continue;
                    }

                    echo $count.', ';
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


    protected function fetchCandles(
        $exchange,
        $symbol_name,
        $resolution,
        $since,
        $chunk_size
    ) {
        $candles = null;
        try {
            $candles = $exchange->fetchCandles(
                $symbol_name,
                $resolution,
                $since,
                $chunk_size
            );
        } catch (\Exception $e) {
            $msg = str_replace("\n", '', strip_tags($e->getMessage()));
            $msg = 197 < strlen($msg) ? substr($msg, 0, 197).'...' : $msg;
            echo PHP_EOL.'Error: '.$msg;
            Log::error('fetchCandles', $exchange->getName(), $symbol_name, $msg);
        }
        return $candles;
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
     * Get first/last candle time
     * @param  int    $exchange_id
     * @param  int    $symbol_id
     * @param  int    $resolution
     * @param string  $get
     * @return int
     */
    protected function getCandleTime(
        int $exchange_id,
        int $symbol_id,
        int $resolution,
        string $get = 'first'
    ): int {
        $query = DB::table('candles')
            ->select('time')
            ->where('exchange_id', $exchange_id)
            ->where('symbol_id', $symbol_id)
            ->where('resolution', $resolution);
        if ('first' === $get) {
            $query->orderBy('time');
        } elseif ('last' === $get) {
            $query->latest('time');
        } else {
            Log::error('unsupported parameter', $get);
            return 0;
        }
        $time = $query->first();
        return is_object($time) ? (int)$time->time : 0;
    }


    protected function saveCandles(
        array $candles,
        int $exchange_id,
        int $symbol_id,
        int $resolution)
    {
        foreach ($candles as $candle) {
            $candle->exchange_id = $exchange_id;
            $candle->symbol_id = $symbol_id;
            $candle->resolution = $resolution;
            try {
                $candle = Series::sanitizeCandle($candle);
                Series::saveCandle($candle);
            } catch (\Exception $e) {
                echo PHP_EOL.'Error: '.$e->getMessage();
                Log::error($e->getMessage());
            }
        }
        return $this;
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
