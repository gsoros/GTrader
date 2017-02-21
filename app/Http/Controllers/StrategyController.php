<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GTrader\Strategy;


class StrategyController extends Controller
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


    public function strategyList(Request $request)
    {
        return response(Strategy::getList(), 200);
    }


    public function strategyNew(Request $request)
    {
        $user_id = Auth::id();
        $name = 'My New '.$request->strategyClass.' Strategy';
        $i = 2;
        while (true)
        {
            $query = DB::table('strategies')
                        ->select('id')
                        ->where('user_id', $user_id)
                        ->where('name', $name)
                        ->first();
            if (!is_object($query))
                break;
            $name = 'My New '.$request->strategyClass.' Strategy #'.$i;
            $i++;
        }
        $strategy = Strategy::make($request->strategyClass,
                                    ['id' => 'new',
                                    'user_id' => $user_id,
                                    'name' => $name]);
        $strategy->save();
        $form = $strategy->toHTML();
        return response($form, 200);
    }


    public function strategyForm(Request $request)
    {
        $strategy_id = intval($request->id);
        if (!($strategy = Strategy::load($strategy_id)))
        {
            error_log('Failed to load strategy ID '.$strategy_id);
            return response('Failed to load strategy.', 403);
        }
        if ($strategy->getParam('user_id') !== Auth::id())
        {
            error_log('That strategy belongs to someone else: ID '.$strategy_id);
            return response('Failed to load strategy.', 403);
        }
        $form = $strategy->toHTML();
        return response($form, 200);
    }


    public function strategyDelete(Request $request)
    {
        $strategy_id = intval($request->id);
        if (!($strategy = Strategy::load($strategy_id)))
        {
            error_log('Failed to load strategy ID '.$strategy_id);
            return response('Strategy not found.', 403);
        }
        if ($strategy->getParam('user_id') !== Auth::id())
        {
            error_log('That strategy belongs to someone else: ID '.$strategy_id);
            return response('Strategy not found.', 403);
        }
        $strategy->delete();
        return response(Strategy::getList(), 200);
    }


    public function strategySave(Request $request)
    {
        $strategy_id = intval($request->id);
        if (!($strategy = Strategy::load($strategy_id)))
        {
            error_log('Failed to load strategy ID '.$strategy_id);
            return response('Strategy not found.', 403);
        }
        if ($strategy->getParam('user_id') !== Auth::id())
        {
            error_log('That strategy belongs to someone else: ID '.$strategy_id);
            return response('Strategy not found.', 403);
        }
        return response($strategy->handleSaveRequest($request), 200);
    }


}
