<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GTrader\Series;
use GTrader\Strategy;
use GTrader\Exchange;
use GTrader\FannTraining;

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
        while (true) {
            $query = DB::table('strategies')
                        ->select('id')
                        ->where('user_id', $user_id)
                        ->where('name', $name)
                        ->first();
            if (!is_object($query)) {
                break;
            }
            $name = $request->strategyClass.' Strategy #'.$i;
            $i++;
        }
        $strategy = Strategy::make(
            $request->strategyClass,
            [   'id' => 'new',
                'user_id' => $user_id,
                'name' => $name]
        );
        $strategy->save();
        $form = $strategy->toHTML();
        return response($form, 200);
    }


    public function form(Request $request)
    {
        $strategy_id = intval($request->id);
        if (!($strategy = Strategy::load($strategy_id))) {
            error_log('Failed to load strategy ID '.$strategy_id);
            return response('Failed to load strategy.', 403);
        }
        if ($strategy->getParam('user_id') !== Auth::id()) {
            error_log('That strategy belongs to someone else: ID '.$strategy_id);
            return response('Failed to load strategy.', 403);
        }
        $form = $strategy->toHTML();
        return response($form, 200);
    }


    public function delete(Request $request)
    {
        $strategy_id = intval($request->id);
        if (!($strategy = Strategy::load($strategy_id))) {
            error_log('Failed to load strategy ID '.$strategy_id);
            return response(Strategy::getListOfUser(Auth::id()), 200);
        }
        if ($strategy->getParam('user_id') !== Auth::id()) {
            error_log('That strategy belongs to someone else: ID '.$strategy_id);
            return response(Strategy::getListOfUser(Auth::id()), 200);
        }
        $strategy->delete();
        return response(Strategy::getListOfUser(Auth::id()), 200);
    }


    public function save(Request $request)
    {
        $strategy_id = intval($request->id);
        if (!($strategy = Strategy::load($strategy_id))) {
            error_log('Failed to load strategy ID '.$strategy_id);
            return response('Strategy not found.', 403);
        }
        if ($strategy->getParam('user_id') !== Auth::id()) {
            error_log('That strategy belongs to someone else: ID '.$strategy_id);
            return response('Strategy not found.', 403);
        }
        $strategy->handleSaveRequest($request);
        $strategy->save();
        return response(Strategy::getListOfUser(Auth::id()), 200);
    }


    public function train(Request $request)
    {
        $strategy_id = intval($request->id);
        if (!($strategy = Strategy::load($strategy_id))) {
            error_log('Failed to load strategy ID '.$strategy_id);
            return response('Strategy not found.', 403);
        }
        if ($strategy->getParam('user_id') !== Auth::id()) {
            error_log('That strategy belongs to someone else: ID '.$strategy_id);
            return response('Strategy not found.', 403);
        }
        $training = FannTraining::where('strategy_id', $strategy_id)
                                ->where(function ($query) {
                                        $query->where('status', 'training')
                                            ->orWhere('status', 'paused');
                                })
                                ->first();
        if (is_object($training)) {
            $html = view('Strategies/FannTrainProgress', ['strategy' => $strategy,
                                                            'training' => $training]);
            return response($html, 200);
        }
        $html = view('Strategies/FannTrainForm', ['strategy' => $strategy]);
        return response($html, 200);
    }


    public function trainStart(Request $request)
    {
        error_log('trainStart '.var_export($request->all(), true));

        $strategy_id = intval($request->id);
        if (!($strategy = Strategy::load($strategy_id))) {
            error_log('Failed to load strategy ID '.$strategy_id);
            return response('Strategy not found.', 403);
        }
        if ($strategy->getParam('user_id') !== Auth::id()) {
            error_log('That strategy belongs to someone else: ID '.$strategy_id);
            return response('Strategy not found.', 403);
        }
        $exchange = $request->exchange;
        if (!($exchange_id = Exchange::getIdByName($exchange))) {
            error_log('Exchange not found ');
            return response('Exchange not found.', 403);
        }
        $symbol = $request->symbol;
        if (!($symbol_id = Exchange::getSymbolIdByExchangeSymbolName($exchange, $symbol))) {
            error_log('Symbol not found ');
            return response('Symbol not found.', 403);
        }
        if (!($resolution = $request->resolution)) {
            error_log('Resolution not found ');
            return response('Resolution not found.', 403);
        }
        $train_start_percent = doubleval($request->train_start_percent);
        $train_end_percent = doubleval($request->train_end_percent);
        if (($train_start_percent >= $train_end_percent) || !$train_end_percent) {
            error_log('Start or end not found ');
            return response('Input error.', 403);
        }
        $test_start_percent = doubleval($request->test_start_percent);
        $test_end_percent = doubleval($request->test_end_percent);
        if (($test_start_percent >= $test_end_percent) || !$test_end_percent) {
            error_log('Start or end not found ');
            return response('Input error.', 403);
        }
        $training = FannTraining::where('strategy_id', $strategy_id)
                                ->where('status', 'training')->first();
        if (is_object($training)) {
            error_log('Strategy id('.$strategy_id.') is already being trained.');
            $html = view('Strategies/FannTrainProgress', [
                    'strategy' => $strategy,
                    'training' => $training]);
            return response($html, 200);
        }
        $candles = new Series(['exchange' => $exchange,
                                'symbol' => $symbol,
                                'resolution' => $resolution,
                                'limit' => 0]);
        $epoch = $candles->getEpoch();
        $last = $candles->getLastInSeries();
        $total = $last - $epoch;
        $train_start = floor($epoch + $total / 100 * $train_start_percent);
        $train_end = ceil($epoch + $total / 100 * $train_end_percent);
        $test_start = floor($epoch + $total / 100 * $test_start_percent);
        $test_end = ceil($epoch + $total / 100 * $test_end_percent);

        if (isset($request->from_scratch)) {
            if (intval($request->from_scratch)) {
                error_log('Starting from scratch.');
                $strategy->destroyFann();
                $strategy->deleteFiles();
                $strategy->createFann();
            }
        }

        $options = [
            'train_start' => $train_start,
            'train_end' => $train_end,
            'test_start' => $test_start,
            'test_end' => $test_end
        ];

        $training = FannTraining::firstOrNew([
                            'strategy_id'   => $strategy_id,
                            'status'        => 'training']);
        $training->strategy_id = $strategy_id;
        $training->status = 'training';
        $training->exchange_id = $exchange_id;
        $training->symbol_id = $symbol_id;
        $training->resolution = $resolution;
        $training->options = $options;
        $training->save();
        $training->resetStatus($strategy);

        $html = view('Strategies/FannTrainProgress', [
                        'strategy' => $strategy,
                        'training' => $training]);

        return response($html, 200);
    }


    public function trainStop(Request $request)
    {
        $strategy_id = intval($request->id);
        if (!($strategy = Strategy::load($strategy_id))) {
            error_log('Failed to load strategy ID '.$strategy_id);
            return response(Strategy::getListOfUser(Auth::id()), 200);
        }
        if ($strategy->getParam('user_id') !== Auth::id()) {
            error_log('That strategy belongs to someone else: ID '.$strategy_id);
            return response(Strategy::getListOfUser(Auth::id()), 200);
        }
        $training = FannTraining::where('strategy_id', $strategy_id)
                         ->where(function ($query) {
                                $query->where('status', 'training')
                                    ->orWhere('status', 'paused');
                        })
                        ->first();
        if (is_object($training)) {
            //$training->status = 'stopped';
            //$training->save();
            $training->delete();
            //$html = view('Strategies/FannTrainProgress', ['strategy' => $strategy]);
            //return response($html, 200);
        }
        return response(Strategy::getListOfUser(Auth::id()), 200);
    }


    public function trainProgress(Request $request)
    {
        $strategy_id = intval($request->id);
        if (!($strategy = Strategy::load($strategy_id))) {
            error_log('Failed to load strategy ID '.$strategy_id);
            return response('Strategy not found.', 404);
        }
        if ($strategy->getParam('user_id') !== Auth::id()) {
            error_log('That strategy belongs to someone else: ID '.$strategy_id);
            return response('Strategy not found.', 403);
        }
        $training = FannTraining::where('strategy_id', $strategy_id)
                                ->where('status', 'training')->first();
        if (!is_object($training)) {
            error_log('Training not found for strategy '.$strategy_id);
            return response('Training not found.', 404);
        }
        return response($training->readStatus($strategy), 200);
    }


    public function trainPause(Request $request)
    {
        $strategy_id = intval($request->id);
        if (!($strategy = Strategy::load($strategy_id))) {
            error_log('Failed to load strategy ID '.$strategy_id);
            return response('Strategy not found.', 404);
        }
        if ($strategy->getParam('user_id') !== Auth::id()) {
            error_log('That strategy belongs to someone else: ID '.$strategy_id);
            return response('Strategy not found.', 403);
        }
        $training = FannTraining::where('strategy_id', $strategy_id)
                                ->where('status', 'training')->first();
        if (!is_object($training)) {
            error_log('Training not found for strategy '.$strategy_id);
            return response('Training not found.', 404);
        }
        $training->status = 'paused';
        $training->save();
        $html = view('Strategies/FannTrainProgress', ['strategy' => $strategy,
                                                    'training' => $training]);
        return response($html, 200);
    }


    public function trainResume(Request $request)
    {
        $strategy_id = intval($request->id);
        if (!($strategy = Strategy::load($strategy_id))) {
            error_log('Failed to load strategy ID '.$strategy_id);
            return response('Strategy not found.', 404);
        }
        if ($strategy->getParam('user_id') !== Auth::id()) {
            error_log('That strategy belongs to someone else: ID '.$strategy_id);
            return response('Strategy not found.', 403);
        }
        $training = FannTraining::where('strategy_id', $strategy_id)
                                ->where('status', 'paused')->first();
        if (!is_object($training)) {
            error_log('Training not found for strategy '.$strategy_id);
            return response('Training not found.', 404);
        }
        $training->status = 'training';
        $training->save();
        $html = view('Strategies/FannTrainProgress', ['strategy' => $strategy,
                                                    'training' => $training]);
        return response($html, 200);
    }
}
