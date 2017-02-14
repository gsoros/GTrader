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

        if (! $chart = session('mainchart'))
        {
            error_log('json: no mainchart in session');
            //$chart = Chart::make(null, ['id' => 'mainchart']);
        }
        else error_log('json: mainchart found in session');

        $json = $chart->handleJSONRequest($request);
        session(['mainchart' => $chart]);


        return response($json, 200);

    }
}
