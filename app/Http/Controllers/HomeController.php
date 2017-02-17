<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GTrader\Page;
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
        if (! $strategy = $chart->getStrategy())
        {
            error_log('home: no strategy in chart, creating new');
            $strategy = Strategy::make();
        }

        $debug = '';
        foreach ($chart->getIndicators() as $i)
            $debug .= 'I: '.$i->getSignature().
                    ' V: '.$i->getParam('display.visible').
                    ' D: '.serialize($i->getParam('depends'))."\n";

        $viewData = [   'chart'             => $chart->toHtml(),
                        'strategy'          => $strategy->toHtml(),
                        'stylesheets'       => Page::getElements('stylesheets'),
                        'scripts_top'       => Page::getElements('scripts_top'),
                        'scripts_bottom'    => Page::getElements('scripts_bottom'),
                        'debug'             => $debug];

        session(['mainchart' => $chart]);

        return view('dashboard')->with($viewData);
    }



}
