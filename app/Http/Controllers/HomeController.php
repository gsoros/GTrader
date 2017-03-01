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
        $chart = Chart::load(Auth::id(), 'mainchart', null, ['autorefresh' => true]);

        Page::add('scripts_bottom',
                    '<script src="/js/Mainchart.js"></script>');
        $chart->addPageElements();

        Page::add('stylesheets',
                    '<link href="/css/nouislider.min.css" rel="stylesheet">');
        Page::add('scripts_bottom',
                    '<script src="/js/nouislider.min.js"></script>');

        $viewData = [   'chart'             => $chart->toHtml(),
                        'strategy'          => Strategy::getListOfUser(Auth::id()),
                        'stylesheets'       => Page::get('stylesheets'),
                        'scripts_top'       => Page::get('scripts_top'),
                        'scripts_bottom'    => Page::get('scripts_bottom'),
                        'debug'             => var_export($chart->getCandles()->getParams(), true)];

        $chart->saveToSession()->save();

        return view('dashboard')->with($viewData);
    }



}
