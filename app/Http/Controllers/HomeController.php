<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GTrader\Exchange;
use GTrader\Chart;
use GTrader\Series;
use GTrader\Strategy;
use GTrader\Indicator;
use GTrader\Util;
use GTrader\TestClass;

class HomeController extends Controller
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

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard(Request $request)
    {
        if (! $chart = session('mainchart'))
        {
            error_log('home: no mainchart in session, creating new');
            $candles = new Series();
            $strategy = Strategy::make();
            $strategy->setParam('config_file', 'test.fann');
            $strategy->setCandles($candles);
            $balance = Indicator::make('Balance');
            $strategy->addIndicator($balance);
            $candles->addIndicator('Ema');;
            $strategy->addIndicator('FannPrediction');
            $strategy->addIndicator('FannSignals');
            $chart = Chart::make(null, [
                        'id' => $request->id,
                        'candles' => $candles,
                        'strategy' => $strategy,
                        'id' => 'mainchart']);
        }
        else error_log('home: mainchart found in session');
        //dump($chart);
        $debug = serialize($chart);

        $viewData = [   'chart'             => $chart->toHtml(),
                        'stylesheets'       => $chart->getPageElements('stylesheets'),
                        'scripts_top'       => $chart->getPageElements('scripts_top'),
                        'scripts_bottom'    => $chart->getPageElements('scripts_bottom'),
                        'debug'             => $debug];
        session(['mainchart' => $chart]);
        return view('dashboard')->with($viewData);
    }



}
