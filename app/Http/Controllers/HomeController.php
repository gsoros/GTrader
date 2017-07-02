<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use GTrader\Page;
use GTrader\Exchange;
use GTrader\Chart;
//use GTrader\Series;
use GTrader\Strategy;
//use GTrader\Indicator;
use GTrader\Util;
use GTrader\Bot;

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
        $chart = Chart::load(Auth::id(), 'mainchart', null, [
            'autorefresh' => true,
            //'disabled' => ['map'],
            'indicators_if_new' => [
                'Ohlc',
            ],
        ]);
        Page::add('scripts_top', '<script src="/js/GTrader.js"></script>');
        Page::add('scripts_bottom', '<script src="/js/Mainchart.js"></script>');
        $chart->addPageElements();

        Page::add('stylesheets', '<link href="/css/nouislider.min.css" rel="stylesheet">');
        Page::add('scripts_bottom', '<script src="/js/nouislider.min.js"></script>');

        $viewData = [
            'chart'             => $chart->toHtml(),
            'strategies'        => Strategy::getListOfUser(Auth::id()),
            'exchanges'         => Exchange::getList(),
            'bots'              => Bot::getListOfUser(Auth::id()),
            'stylesheets'       => Page::get('stylesheets'),
            'scripts_top'       => Page::get('scripts_top'),
            'scripts_bottom'    => Page::get('scripts_bottom'),
            'debug'             => var_export($chart->getCandles()->getParams(), true)
        ];

        $chart->saveToSession()->save();

        return view('dashboard')->with($viewData);
    }


    public function test()
    {
        $arr = [
            'a' => [
                'b' => [
                    'c' => [
                        'v1',
                        ['d' => 'i1'],
                        ['1' => 's1'],
                    ]
                ]
            ]
        ];

        $list = ['a', 'b', 'c', 1, 'd'];
        //$res = Util::arrEl($arr, $list);

        $key = 'a.b.c.1';
        $res = Arr::get($arr, $key);

        return view('basic')->with([
            'content' => json_encode($res),
        ]);

        $count = 100000;
        $baseline = memory_get_usage();

        for ($i=0; $i<$count; $i++) {
            $key = bin2hex(random_bytes(50));
            $max = getrandmax();
            $val = (rand(0, $max) - ($max / 2)) / $max / 2 ;
            $a[] = [$key => $val];
        }
        return view('basic')->with([
            'content' =>
                $count.' pieces of random key-value pairs like <code>["'.
                $key.'" => '.$val.']</code> use approx. '.
                Util::humanBytes(memory_get_usage() - $baseline)
        ]);
    }
}
