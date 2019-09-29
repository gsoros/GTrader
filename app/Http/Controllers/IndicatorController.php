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
            return response('Could not load owner.', 200);
        }
        $sig = urldecode($request->signature);
        if (! $owner->hasIndicator($sig)) {
            Log::error('Indicator not found. Sig: '.$sig);
            return response('Indicator not found.', 200);
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
            Log::error('Indicator '.$sig.' not found');
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


    public function sources(Request $request)
    {
        if (! $owner = $this->loadOwner($request)) {
            return response('Could not load owner.', 403);
        }
        return response(json_encode($owner->getAvailableSources()), 200);
    }

    protected function loadOwner(Request $request)
    {
        if (!$request->owner_class) {
            Log::error('Owner_class is not set');
            return false;
        }
        if ('Chart' === $request->owner_class) {
            if (!isset($request->name)) {
                Log::error('Name is not set');
                return false;
            }
            if (! $chart = Chart::loadFromSession($request->name)) {
                Log::error('No chart in session. '.json_encode($request->all()));
                return false;
            }
            return $chart;
        }
        if ('Strategy' === $request->owner_class) {
            if (!isset($request->owner_id)) {
                Log::error('Owner_id is not set');
                return false;
            }
            if (! $strategy = Strategy::load($request->owner_id)) {
                Log::error('Strategy not found');
                return false;
            }
            if ($strategy->getParam('user_id') !== Auth::id()) {
                Log::error('Strategy not owned by this user');
                return false;
            }
            return $strategy;
        }
        Log::error('Owner_class not implemented:', $request->owner_class);
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
