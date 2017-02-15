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
        if (! $chart = session($request->id))
        {
            error_log('JSON: no chart in session');
            return response('No such chart in session.', 403);
        }

        $json = $chart->handleJSONRequest($request);
        session([$request->id => $chart]);

        return response($json, 200);
    }


    public function settings_form(Request $request)
    {
        if (! $chart = session($request->id))
        {
            error_log('settings_form: no chart in session');
            return response('No such chart in session.', 403);
        }
        $form = $chart->handleSettingsFormRequest($request);
        session([$request->id => $chart]);

        return response($form, 200);
    }
}
