<?php

namespace GTrader;

use Illuminate\Support\Facades\DB;

class Aggregator
{
    public function aggregate()
    {

        //ignore_user_abort(true);

        $lock = str_replace('::', '_', str_replace('\\', '_', __METHOD__));
        if (!Lock::obtain($lock)) {
            error_log('Another aggregator process is running.');
            return null;
        }

        try {
            if (!count(DB::select(DB::raw('show tables like "exchanges"')))) {
                error_log('Aggregator::aggregate() Exchanges table does not (yet) exist in the database.');
                return null;
            }
        } catch (\Exception $e) {
            error_log('Aggregator::aggregate() Database is not ready (yet).');
            return null;
        }

        $default_exchange = Exchange::make();
        foreach ($default_exchange->getParam('available_exchanges') as $exchange_class) {
            echo 'Exchange: '.$exchange_class;
            $exchange = Exchange::make($exchange_class);
            $exchange_id = null;
            $exchange_o = DB::table('exchanges')
                ->select('id')
                ->where('name', $exchange->getParam('local_name'))
                ->first();
            if (is_object($exchange_o)) {
                $exchange_id = $exchange_o->id;
            }
            if (!$exchange_id) {
                $exchange_id = DB::table('exchanges')
                                    ->insertGetId([  'name' => $exchange->getParam('local_name'),
                                                'long_name' => $exchange->getParam('long_name')]);
            }
            echo ' ID: '.$exchange_id."\n";
            $symbols = $exchange->getParam('symbols');
            if (!is_array($symbols)) {
                continue;
            }
            foreach ($symbols as $symbol_local => $symbol) {
                echo 'Symbol: '.$symbol_local;
                if (!is_array($symbol['resolutions'])) {
                    continue;
                }
                $symbol_id = null;
                $symbol_o = DB::table('symbols')
                    ->select('id')
                    ->where('name', $symbol_local)
                    ->where('exchange_id', $exchange_id)
                    ->first();
                if (is_object($symbol_o)) {
                    $symbol_id = $symbol_o->id;
                }
                if (!$symbol_id) {
                    $symbol_id = DB::table('symbols')
                        ->insertGetId([
                            'name' => $symbol_local,
                            'exchange_id' => $exchange_id,
                            'long_name' => $symbol['long_name']]);
                }

                echo ' ID: '.$symbol_id."\n";
                foreach ($symbol['resolutions'] as $resolution => $res_name) {
                    //set_time_limit(59);

                    $time = DB::table('candles')
                            ->select('time')
                            ->where('exchange_id', $exchange_id)
                            ->where('symbol_id', $symbol_id)
                            ->where('resolution', $resolution)
                            ->latest('time')
                            ->first();
                    $time = is_object($time) ? $time->time : 0;
                    echo 'Res: '.$res_name.' Last: '.date('Y-m-d H:i', $time)."\n";
                    //if ($time > time() - $resolution) continue;

                    $candles = $exchange->getCandles(
                        $symbol_local,
                        $resolution,
                        $time - $resolution - 1,
                        100000
                    );
                    //dd($candles);

                    if (!is_array($candles)) {
                        continue;
                    }
                    if (!count($candles)) {
                        continue;
                    }
                    foreach ($candles as $candle) {
                        $candle->exchange_id = $exchange_id;
                        $candle->symbol_id = $symbol_id;
                        $candle->resolution = $resolution;
                        Series::saveCandle($candle);
                    }
                    echo count($candles)." processed\n";
                }
            }
        }

        Lock::release($lock);

    }
}
