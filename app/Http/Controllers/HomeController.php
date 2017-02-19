<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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
        $chart = Chart::load('mainchart');

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
                        'stylesheets'       => Page::get('stylesheets'),
                        'scripts_top'       => Page::get('scripts_top'),
                        'scripts_bottom'    => Page::get('scripts_bottom'),
                        'debug'             => $debug];

        session(['mainchart' => $chart]);
        $chart->save();

        return view('dashboard')->with($viewData);
    }



}
