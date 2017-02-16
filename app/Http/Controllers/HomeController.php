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

            $chart = Chart::make(null, ['id' => 'mainchart']);
        }
        else error_log('home: mainchart found in session');

        $d = '';
        foreach ($chart->getIndicators() as $i)
            $d .= 'I: '.$i->getSignature().' V: '.$i->getParam('display.visible')."\n";
        $debug = $d; //print_r($chart, true);

        $viewData = [   'chart'             => $chart->toHtml(),
                        'stylesheets'       => $chart->getPageElements('stylesheets'),
                        'scripts_top'       => $chart->getPageElements('scripts_top'),
                        'scripts_bottom'    => $chart->getPageElements('scripts_bottom'),
                        'debug'             => $debug];

        session(['mainchart' => $chart]);

        return view('dashboard')->with($viewData);
    }



}
