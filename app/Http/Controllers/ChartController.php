<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use GTrader\Util;
use GTrader\Series;
use GTrader\Strategy;
use GTrader\Chart;
use GTrader\Indicator;
use GTrader\Log;

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
        Log::debug('.');
        if (! $chart = Chart::loadFromSession($request->name)) {
            Log::error('No chart in session');
            return response('No such chart in session.', 403);
        }

        $json = $chart->handleJSONRequest($request);
        $chart->saveToSession();
        //$chart->save();
        return response($json, 200);
    }


    public function image(Request $request)
    {
        //Log::debug('.');
        if (! $chart = Chart::loadFromSession($request->name)) {
            Log::error('No chart in session');
            return response('No such chart in session.', 403);
        }

        set_time_limit(60);
        $chart->handleImageRequest($request);
        $image = $chart->getImage();

        $chart->purgeIndicators();
        $chart->saveToSession();
        //$chart->save();

        return response($image, 200);
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
            Log::error('No chart in session');
            return response('No such chart in session.', 403);
        }
        if (! ($strategy = Strategy::load($request->strategy))) {
            Log::error('Could not load strategy');
            return response('Strategy not found.', 403);
        }
        if ($strategy->getParam('user_id') !== Auth::id()) {
            Log::error('Strategy owner mismatch');
            return response('Strategy not found.', 403);
        }
        $chart->setStrategy($strategy);
        $chart->saveToSession();
        $chart->save();
        return response('Chart successfully updated.', 200);
    }


    public function settingsForm(Request $request)
    {
        if (! $chart = Chart::loadFromSession($request->name)) {
            Log::error('No chart in session');
            return response('No such chart in session.', 403);
        }
        $form = $chart->viewIndicatorsList($request);
        $chart->saveToSession();

        return response($form, 200);
    }


    public function delete(Request $request)
    {
        if (! $chart = Chart::loadFromSession($request->name)) {
            Log::error('no chart in session');
            if (! $chart = Chart::loadFromDB(Auth::id(), $request->name)) {
                Log::error('no chart in db');
                return response('No such chart.', 403);
            }
        }

        $result = $chart->deleteFromSession()->delete();
        return view('basic')->with([
            'content' => ($result ? 'Deleted.' : 'Error.'),
        ]);
    }
}
