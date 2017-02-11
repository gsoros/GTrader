<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use GTrader\Util;
use GTrader\Series;
use GTrader\Strategy;
use GTrader\Chart;
use GTrader\Candle;
use GTrader\Indicator;

class ChartController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function JSON(Request $request)
    {
        //$u = new Updater;
        //$u->fetchCandles();
        //exit();
        //$autoloader = require '../vendor/autoload.php';
        //$autoloader->loadClass('PHPlot');
        //Util::dump($autoloader);

        /*
        $c = Candle::find(1234);
        $c->id = 5678;
        $c->save();
        dump($c);
        */
        /*
        Util::dump($c);
        $c->unsetIndicator('sma_5_close');
        Util::dump($c);
        */
        //$c = Candle::where('time', 1485797340)->where('open', 897.36)->first();
        //$c->some_new_attrib = 999.99;
        //echo gettype($c);
        //$c->save();
        //unset($c);
        //$c = Candle::where('time', 1485797340)->first();
        //Util::dump($c);
        $candles = new Series(['resolution' => 3600]);
        //$candles->reset();
        //while ($c = $candles->next())
        //    echo $c->time.'<br />';
        $strategy = Strategy::make();
        $strategy->setParam('config_file', 'test.fann');
        $strategy->setCandles($candles);
        $chart = Chart::make(null, [
                    'id' => $request->id,
                    'candles' => $candles,
                    'strategy' => $strategy]);
        //$chart->set('start', 0);
        //$chart->setCandles($candles);
        //$chart->setStrategy($strategy);
        $balance = Indicator::make('Balance');
        //$balance ->setParam('display.y_axis_pos', 'right');
        //dd($balance);
        $strategy->addIndicator($balance);
        //$strategy->addIndicator('Balance');
        $candles->addIndicator('Ema');
        //$candles->calculateIndicators();
        $strategy->addIndicator('FannPrediction');
        $strategy->addIndicator('FannSignals');
        //$strategy->calculateIndicators();
        //dd($candles);
        $json = $chart->handleJSONRequest($request);

        //debug($candles); exit();
        //dd(xdebug_get_headers());
        //dd($chart);
        //$candles->reset();
        //dd($candles->last());

        return response($json, 200);
        //->header('Content-Type', 'image/png');

    }
}
