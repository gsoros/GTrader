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

class IndicatorController extends Controller
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


    public function form(Request $request)
    {
        if (! $owner = $this->loadOwner($request)) {
            return response('Could not load owner.', 403);
        }
        $sig = urldecode($request->signature);
        if (! $owner->hasIndicator($sig)) {
            error_log('indicatorController::form() indicator not found. Sig: '.$sig);
            return response('Indicator not found.', 403);
        }
        $form = $owner->handleIndicatorFormRequest($request);
        $this->saveOwner($owner);

        return response($form, 200);
    }


    public function create(Request $request)
    {
        if (! $owner = $this->loadOwner($request)) {
            return response('Could not load owner.', 403);
        }
        $form = $owner->handleIndicatorNewRequest($request);
        $this->saveOwner($owner);

        return response($form, 200);
    }


    public function delete(Request $request)
    {
        if (! $owner = $this->loadOwner($request)) {
            return response('Could not load owner.', 403);
        }
        $sig = urldecode($request->signature);
        if (! $owner->hasIndicator($sig)) {
            error_log('indicatorDelete: indicator '.$sig.' not found');
            return response('Indicator not found.', 403);
        }
        $form = $owner->handleIndicatorDeleteRequest($request);
        $this->saveOwner($owner);

        return response($form, 200);
    }


    public function save(Request $request)
    {
        if (! $owner = $this->loadOwner($request)) {
            return response('Could not load owner.', 403);
        }
        $form = $owner->handleIndicatorSaveRequest($request);

        $this->saveOwner($owner);

        return response($form, 200);
    }


    protected function loadOwner(Request $request)
    {
        if (!isset($request->owner_class)) {
            error_log('IndicatorController::load() owner_class is not set');
            return false;
        }
        if ('Chart' === $request->owner_class) {
            if (!isset($request->name)) {
                error_log('IndicatorController::load() name is not set');
                return false;
            }
            if (! $chart = Chart::loadFromSession($request->name)) {
                error_log('IndicatorController::load() no chart in session. '.json_encode($request->all()));
                return false;
            }
            return $chart;
        }
        if ('Strategy' === $request->owner_class) {
            if (!isset($request->owner_id)) {
                error_log('IndicatorController::load() owner_id is not set');
                return false;
            }
            if (! $strategy = Strategy::load($request->owner_id)) {
                error_log('IndicatorController::load() strategy not found');
                return false;
            }
            if ($strategy->getParam('user_id') !== Auth::id()) {
                error_log('IndicatorController::load() strategy not owned by this user');
                return false;
            }
            return $strategy;
        }
        error_log('IndicatorController::load() owner_class not implemented');
        return false;
    }


    protected function saveOwner($owner)
    {
        if (method_exists($owner, 'saveToSession')) {
            $owner->saveToSession();
        }
        if (method_exists($owner, 'save')) {
            $owner->save();
        }
        return $this;
    }
}
