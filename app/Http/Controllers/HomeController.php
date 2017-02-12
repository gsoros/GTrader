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
    public function dashboard()
    {

        //$viewData = ['debug' => var_export(Exchange::getTicker(), true)];
        //$ema = Indicator::make('ema', ['length' => 10]);
        //$ema->setCandles($candles);
        //$candles = new Series(20);
        //$candles->addIndicator('ema');
        //$candles->addIndicator('ema', ['length' => 20]);
        //$candles->addIndicator('ema', ['price' => 'prediction_fixed']);
        //dd($candles);
        //$strategy = Strategy::make('Fann', $candles, ['fann.config_file' => 'run.fann']);
        //$strategy->addIndicator('fannPrediction');
        //$strategy->addIndicator('fannTrades');
        //$strategy->addIndicator('balance');
        //$strategy->calculateIndicators();
        //$c = $strategy->getCandles();
        //$c->addIndicator('ema', ['price' => 'fannprediction', 'length' => 20]);
        //dump($c);
        //$c->calculateIndicators();
        //$strategy->getCandles()->calculateIndicators();
        //$ind = $strategy->findIndicator('balance_fixed');
        //dd($strategy);
        /*
        $p = $strategy->getIndicators()[0];
        $pa = $p->getOwner();
        dump($pa);
        $pa->set_bias_compensation(999);
        dump($p->getOwner());
        exit();
        */
        //$test = new TestClass('b_val_new');
        //$test->setParam('sub_1.c', 'c_val');
        //$debug = Util::getDump($test->getParamsExcept('sub_1.a'))
        //$e = Exchange::make();
        //$e = TestClass::make('TestChild', ['param1' => 'val1']);
        //$e = TestClass::make();
        //$e = TestClass::someMethod();
        //$debug = Util::getDump($e);
        $debug = var_export(Exchange::getESR(), true);

        //$autoloader = require "../vendor/autoload.php";
        //$autoloader->loadClass("\GTrader\TestClass");
        //$debug = var_export($autoloader, true);
        $chart = Chart::make();
        $candles = Series::make();
        $chart->setCandles($candles);

        $viewData = [   'chart'     => $chart->toHtml(),
                        'scripts'   => $chart->getScriptsHtml(),
                        'debug'     => $debug];

        return view('dashboard')->with($viewData);
    }



}
