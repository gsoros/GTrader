<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
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
        error_log('ChartController::JSON()');
        if (! $chart = Chart::loadFromSession($request->name)) {
            error_log('JSON: no chart in session');
            return response('No such chart in session.', 403);
        }

        $json = $chart->handleJSONRequest($request);
        $chart->saveToSession();
        //$chart->save();
        return response($json, 200);
    }


    public function image(Request $request)
    {
        //error_log('ChartController::image()');
        if (! $chart = Chart::loadFromSession($request->name)) {
            error_log('image: no chart in session');
            return response('No such chart in session.', 403);
        }

        set_time_limit(480);
        $chart->handleImageRequest($request);
        $chart->purgeIndicators();
        $chart->saveToSession();
        //$chart->save();
        return response($chart->getImage(), 200);
    }


    public function strategySelectorOptions(Request $request)
    {
        $selected_strategy = null;
        if (isset($request->selected)) {
            $selected_strategy = intval($request->selected);
        } elseif ($chart = Chart::loadFromSession($request->name)) {
            if ($strategy = $chart->getStrategy()) {
                $selected_strategy = $strategy->getParam('id');
            }
        }

        $selector = Strategy::getSelectorOptions(Auth::id(), $selected_strategy);
        return response($selector, 200);
    }


    public function strategySelect(Request $request)
    {
        if (! $chart = Chart::loadFromSession($request->name)) {
            error_log('strategySelect: no chart in session');
            return response('No such chart in session.', 403);
        }
        if (! ($strategy = Strategy::load($request->strategy))) {
            error_log('strategySelect: could not load strategy');
            return response('Strategy not found.', 403);
        }
        if ($strategy->getParam('user_id') !== Auth::id()) {
            error_log('strategySelect: strategy owner mismatch');
            return response('Strategy not found.', 403);
        }
        /*
        //error_log('strategySelect() copying indicators');
        if ($old_strategy = $chart->getStrategy()) {
            foreach ($old_strategy->getIndicators() as $indicator) {
                $strategy->addIndicator($indicator);
            }
        }
        */
        $chart->setStrategy($strategy);
        $chart->saveToSession();
        $chart->save();
        return response('Chart successfully updated.', 200);
    }


    public function settingsForm(Request $request)
    {
        if (! $chart = Chart::loadFromSession($request->name)) {
            error_log('settingsForm: no chart in session');
            return response('No such chart in session.', 403);
        }
        $format = 400 < intval($request->width) ? 'long' : 'short';
        $form = $chart->viewIndicatorsList($request);
        $chart->saveToSession();

        return response($form, 200);
    }


    public function delete(Request $request)
    {
        if (! $chart = Chart::loadFromSession($request->name)) {
            error_log('no chart in session');
            if (! $chart = Chart::loadFromDB(Auth::id(), $request->name)) {
                error_log('no chart in db');
                return response('No such chart.', 403);
            }
        }

        $chart->deleteFromSession()->delete();
        return response('OK', 200);
    }

}
