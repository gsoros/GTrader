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
        if (! $chart = Chart::loadFromSession($request->name))
        {
            error_log('JSON: no chart in session');
            return response('No such chart in session.', 403);
        }

        $json = $chart->handleJSONRequest($request);
        session([$request->name => $chart]);

        return response($json, 200);
    }


    public function settingsForm(Request $request)
    {
        if (! $chart = Chart::loadFromSession($request->name))
        {
            error_log('settingsForm: no chart in session');
            return response('No such chart in session.', 403);
        }
        $form = $chart->handleSettingsFormRequest($request);
        session([$request->name => $chart]);

        return response($form, 200);
    }


    public function indicatorForm(Request $request)
    {
        if (! $chart = Chart::loadFromSession($request->name))
        {
            error_log('indicatorForm: no chart in session');
            return response('No such chart in session.', 403);
        }
        if (! $chart->hasIndicator($request->signature))
        {
            error_log('indicatorForm: indicator not found in chart');
            return response('No such indicator in that chart.', 403);
        }
        $form = $chart->handleIndicatorFormRequest($request);
        session([$request->name => $chart]);

        return response($form, 200);
    }


    public function indicatorNew(Request $request)
    {
        if (! $chart = Chart::loadFromSession($request->name))
        {
            error_log('indicatorNew: no chart in session');
            return response('No such chart in session.', 403);
        }
        $form = $chart->handleIndicatorNewRequest($request);
        session([$request->name => $chart]);
        $chart->save();

        return response($form, 200);
    }


    public function indicatorDelete(Request $request)
    {
        if (! $chart = Chart::loadFromSession($request->name))
        {
            error_log('indicatorDelete: no chart in session');
            return response('No such chart in session.', 403);
        }
        if (! $chart->hasIndicator($request->signature))
        {
            error_log('indicatorDelete: indicator not found in chart');
            return response('No such indicator in that chart.', 403);
        }
        $form = $chart->handleIndicatorDeleteRequest($request);
        session([$request->name => $chart]);
        $chart->save();

        return response($form, 200);
    }


    public function indicatorSave(Request $request)
    {
        if (! $chart = Chart::loadFromSession($request->name))
        {
            error_log('indicatorSave: no chart in session');
            return response('No such chart in session.', 403);
        }
        $form = $chart->handleIndicatorSaveRequest($request);
        session([$request->name => $chart]);
        $chart->save();

        return response($form, 200);
    }
}
