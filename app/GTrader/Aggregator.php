<?php

namespace GTrader;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

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
    public function aggregate(array $options = [])
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

        $single_exchange = Arr::get($options, 'exchange');
        $single_symbol = Arr::get($options, 'symbol');
        $single_resolution = Arr::get($options, 'resolution');
        $direction = Arr::get($options, 'direction') ?? 'both';
        $requested_chunk_size = Arr::get($options, 'chunk_size');

        echo '['.date('Y-m-d H:i:s').'] '.__METHOD__;

        foreach ($this->getExchanges($single_exchange) as $exchange_class) {
            $exchange = $this->getExchange($exchange_class);
            $exchange_id = $exchange->getId();
            $symbols = $exchange->getSymbols([
                'get' => ['configured'],
                'name' => $single_symbol,
                'resolution' => $single_resolution,
            ]);

            if (!is_array($symbols) || !count($symbols)) {
                continue;
            }
            $chunk_size = $requested_chunk_size ?? $exchange->getParam('aggregator_chunk_size', 100);
            $delay = $exchange->getParam('aggregator_delay', 0);
            echo PHP_EOL.$exchange->getName();

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

                echo ' '.$symbol_name;

                foreach ($symbol['resolutions'] as $resolution => $res_name) {
                    echo ' '.$res_name.' ';
                    $right_count = $left_count = 0;

                    if (in_array($direction, ['fwd', 'right', 'both'])) {
                        $last = $this->getCandleTime(
                            $exchange_id,
                            $symbol_id,
                            $resolution,
                            'last'
                        );
                        $last = $last ? $last : floor(time() / $resolution) * $resolution + $resolution;
                        $since = $last - $resolution;
                        $since = $since > 0 ? $since : 0;

                        //echo ' ('.date('Y-m-d H:i', $since).'):';
                        //flush();

                        $right_candles = $this->fetchCandles(
                            $exchange,
                            $symbol_name,
                            $resolution,
                            $since,
                            $chunk_size
                        );
                        $right_count = count($right_candles);
                        $this->saveCandles($right_candles, $exchange_id, $symbol_id, $resolution);
                    }

                    if (in_array($direction, ['rev', 'left', 'both'])) {
                        $epoch_key = 'epochs.'.str_replace('.', '_', $symbol_name);
                        $epoch = $exchange->getGlobalOption($epoch_key);
                        $first = $this->getCandleTime(
                            $exchange_id,
                            $symbol_id,
                            $resolution,
                            'first'
                        );
                        if (!$epoch || $epoch < $first) {
                            usleep($delay);
                            $fetch_last = $first ? $first : floor(time() / $resolution) * $resolution;
                            $left_request_first = $fetch_last - $chunk_size * $resolution;
                            $left_candles = $this->fetchCandles(
                                $exchange,
                                $symbol_name,
                                $resolution,
                                $left_request_first,
                                $chunk_size
                            );
                            if ($left_count = count($left_candles)) {
                                $left_result_first = reset($left_candles);
                                $left_result_start = $left_result_first->time;
                                $left_result_last = $left_candles[$left_count - 1];
                                $left_result_end = $left_result_last->time;
                                //dump($left_request_first, $left_result_start, $left_request_first + $chunk_size * $resolution, $left_result_end);
                                $left_duplicates = 0;
                                $left_times = $left_duplicate_times = [];
                                foreach ($left_candles as $key => $candle) {
                                    $sequence = $candle->time / $resolution;
                                    $left_times[] = $sequence;
                                    if (DB::table('candles')->where([
                                            ['time', $candle->time],
                                            ['exchange_id', $exchange_id],
                                            ['symbol_id', $symbol_id],
                                            ['resolution', $resolution],
                                        ])->exists()) {
                                        $left_duplicates++;
                                        $left_duplicate_times[] = $sequence;
                                        unset($left_candles[$key]);
                                    }
                                }
                                //dump($left_times);
                                if ($left_duplicates) {
                                    echo ' ['.$left_duplicates.']';
                                    if ($left_duplicates === $left_count) {
                                        Log::debug('All duplicates, setting '.$exchange->getName().' '.$symbol_name.' epoch to '.$first);
                                        $exchange->setGlobalOption($epoch_key, $first);
                                    }
                                }
                                if ($left_count < $chunk_size) {
                                    Log::debug('Less recived: ', $left_count);
                                    //$exchange->setGlobalOption($epoch_key, $left_result_start);
                                }
                                $remaining = count($left_candles);
                                if ($first
                                    && isset($left_candles[$remaining - 1])
                                    && ($left_candles[$remaining - 1]->time < ($first - $resolution))
                                ) {
                                    $gap = $first - $resolution - $left_candles[$remaining - 1]->time;
                                    Log::error('Gap of '.$gap.'s detected at '.$first,
                                        $exchange->getName(), $symbol_name,
                                        $resolution, $chunk_size,
                                        $first / $resolution,
                                        $left_candles[$remaining - 1]->time / $resolution,
                                        $left_request_first / $resolution,
                                        $left_result_start / $resolution,
                                        $left_result_end / $resolution,
                                        $left_duplicate_times,
                                        $left_times
                                    );
                                    echo '[GAP] ';
                                }
                                $this->saveCandles(
                                    array_reverse($left_candles),
                                    $exchange_id,
                                    $symbol_id,
                                    $resolution
                                );
                            } elseif ($first) {
                                Log::debug('Setting '.$exchange->getName().' '.$symbol_name.' epoch to '.$first);
                                $exchange->setGlobalOption($epoch_key, $first);
                            }
                        }
                    }
                    echo $left_count ? $left_count.'<-' : '';
                    echo $right_count ? '->'.$right_count : '';
                    echo (!$left_count && !$right_count) ? '0,' : ',';
                    usleep($delay);
                }
            }
        }
        echo PHP_EOL.'All done.'.PHP_EOL;

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
            echo PHP_EOL.'Error: '.$msg.PHP_EOL;
            Log::error('fetchCandles', $exchange->getName(), $symbol_name, $msg);
            return [];
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
                if (!Series::candleValid($candle)) {
                    throw new \Exception('invalid candle: '.json_encode($candle));
                    continue;
                }
                Series::saveCandle($candle);
            } catch (\Exception $e) {
                echo PHP_EOL.'Error: '.$e->getMessage();
                Log::error($e->getMessage());
            }
        }
        return $this;
    }

    protected function getExchanges(string $single_exchange = null)
    {
        return Exchange::getAvailable([
            'get' => ['configured'],
            'name' => $single_exchange,
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
