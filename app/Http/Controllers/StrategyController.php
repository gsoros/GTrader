<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GTrader\Strategy;
use GTrader\Exchange;

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


    public function list(Request $request)
    {
        return response(Strategy::getListOfUser(Auth::id()), 200);
    }


    public function new(Request $request)
    {
        $user_id = Auth::id();
        $name = $request->strategyClass.' Strategy';
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
            $name = $request->strategyClass.' Strategy #'.$i;
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


    public function form(Request $request)
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


    public function delete(Request $request)
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
        return response(Strategy::getListOfUser(Auth::id()), 200);
    }


    public function save(Request $request)
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
        $strategy->handleSaveRequest($request);
        $strategy->save();
        return response(Strategy::getListOfUser(Auth::id()), 200);
    }


    public function trainForm(Request $request)
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
        //sleep(5);
        $html = view('Strategies/FannTrainForm', ['strategy' => $strategy]);
        return response($html, 200);
    }


    public function trainStart(Request $request)
    {
        error_log('trainStart '.var_export($request->all(), true));

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
        if (!($exchange_id = Exchange::getIdByName($request->exchange)))
        {
            error_log('Exchange not found ');
            return response('Exchange not found.', 403);
        }
        if (!($symbol_id = Exchange::getSymbolIdByExchangeSymbolName($request->exchange, $request->symbol)))
        {
            error_log('Symbol not found ');
            return response('Symbol not found.', 403);
        }
        error_log('trainStart Ex:'.$exchange_id.' Sy: '.$symbol_id);
        $html = view('Strategies/FannTrainForm', ['strategy' => $strategy]);
        return response($html, 200);;
    }


}
