<?php

namespace GTrader;

use GTrader\Exchange;
use GTrader\Candle;

class Aggregator
{
    public function aggregate()
    {

        ignore_user_abort(true);
        $lockfile_path = storage_path(DIRECTORY_SEPARATOR.'tmp'.
                                    DIRECTORY_SEPARATOR.'lock-'.basename(__FILE__));
        $lockfile = fopen($lockfile_path, 'c+');
        if (!$lockfile || !flock($lockfile, LOCK_EX | LOCK_NB)) {
            throw new \Exception('Another process is running.');
            exit();
        }

        $default_exchange = Exchange::make();
        foreach ($default_exchange->getParam('available_exchanges') as $exchange_class)
        {
            echo 'Exchange: '.$exchange_class."\n";
            $exchange = Exchange::make($exchange_class);
            $symbols = $exchange->getParam('symbols');
            if (!is_array($symbols)) continue;
            foreach ($symbols as $symbol_local => $symbol)
            {
                echo 'Symbol: '.$symbol_local."\n";
                if (!is_array($symbol['resolutions'])) continue;
                foreach ($symbol['resolutions'] as $resolution => $res_name)
                {

                    set_time_limit(59);

                    $time = Candle::select('time')
                            ->where('exchange', $exchange->getParam('local_name'))
                            ->where('symbol', $symbol_local)
                            ->where('resolution', strval($resolution))
                            ->latest('time')
                            ->first();
                    $time = is_object($time) ? $time->time : 0;
                    echo 'Res: '.$resolution.' Last: '.gmdate('Y-m-d H:i', $time)."\n";
                    //if ($time > time() - $resolution) continue;

                    $params = [ 'since'         => $time - 1 * $resolution - 1,
                                'resolution'    => $resolution,
                                'symbol'        => $symbol_local,
                                'size'          => 100000]; //Last ID: 1364210
                    $candles = $exchange->getCandles($params);
                    //dd($candles);

                    if (!is_array($candles)) continue;
                    if (!count($candles)) continue;

                    $counter = 0;
                    foreach($candles as $candle) {

                        $counter++;
                        $uts = substr($candle[0], 0, -3);
                        $date = date('Y-m-d H:i', $uts);
                        //echo $date.', '; flush();

                        $new_candle = new Candle();
                        $new_candle->open = $candle[1];
                        $new_candle->high = $candle[2];
                        $new_candle->low = $candle[3];
                        $new_candle->close = $candle[4];
                        $new_candle->volume = $candle[5];
                        $new_candle->time = $uts;
                        $new_candle->exchange = $exchange->getParam('local_name');
                        $new_candle->symbol = $symbol_local;
                        $new_candle->resolution = $resolution;
                        $new_candle->save();
                        //echo 'This: '.gmdate('Y-m-d H:i', $new_candle->time).'<br />';
                        //dump($new_candle);
                    }
                    echo $counter." processed\n";
                }
            }
        }

        flock($lockfile, LOCK_UN);
        fclose($lockfile);
        unlink($lockfile_path);

    }
}
