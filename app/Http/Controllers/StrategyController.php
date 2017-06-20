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
use GTrader\Plot;

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


    public function create(Request $request)
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
                'name' => $name,
                'description' => $request->strategyClass.' strategy']
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
            $html = view('Strategies/FannTrainProgress', [
                'strategy' => $strategy,
                'training' => $training
            ]);
            return response($html, 200);
        }

        $default_prefs = [];
        foreach (['train', 'test', 'verify'] as $item) {
            $default_prefs[$item.'_start_percent'] =
                \Config::get('GTrader.FannTraining.'.$item.'_range.start_percent');
            $default_prefs[$item.'_end_percent'] =
                \Config::get('GTrader.FannTraining.'.$item.'_range.end_percent');
        }
        foreach (['crosstrain', 'reset_after', 'maximize_for'] as $item) {
            $default_prefs[$item] = \Config::get('GTrader.FannTraining.'.$item);
        }

        $html = view('Strategies/FannTrainForm', [
            'strategy' => $strategy,
            'preferences' => Auth::user()->getPreference('fann_training', $default_prefs),
        ]);
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

        $training = FannTraining::where('strategy_id', $strategy_id)
                        ->where('status', 'training')->first();
        if (is_object($training)) {
            error_log('Strategy id('.$strategy_id.') is already being trained.');
            $html = view('Strategies/FannTrainProgress', [
                'strategy' => $strategy,
                'training' => $training
            ]);
            return response($html, 200);
        }

        $prefs = [];
        foreach (['train', 'test', 'verify'] as $item) {
            ${$item.'_start_percent'} = doubleval($request->{$item.'_start_percent'});
            ${$item.'_end_percent'} = doubleval($request->{$item.'_end_percent'});
            if ((${$item.'_start_percent'} >= ${$item.'_end_percent'}) || !${$item.'_end_percent'}) {
                error_log('Start or end not found for '.$item);
                return response('Input error.', 403);
            }
            $prefs[$item.'_start_percent'] = ${$item.'_start_percent'};
            $prefs[$item.'_end_percent'] = ${$item.'_end_percent'};
        }
        foreach (['crosstrain', 'reset_after', 'maximize_for'] as $item) {
            if (isset($request->$item)) {
                $prefs[$item] = $request->$item;
            }
        }
        Auth::user()->setPreference('fann_training', $prefs)->save();

        $candles = new Series([
            'exchange' => $exchange,
            'symbol' => $symbol,
            'resolution' => $resolution,
            'limit' => 0
        ]);
        $epoch = $candles->getEpoch();
        $last = $candles->getLastInSeries();
        $total = $last - $epoch;
        $options = [];
        foreach (['train', 'test', 'verify'] as $item) {
            $options[$item.'_start'] = floor($epoch + $total / 100 * ${$item.'_start_percent'});
            $options[$item.'_end']   = ceil( $epoch + $total / 100 * ${$item.'_end_percent'});
        }

        $options['crosstrain'] = 0;
        if (isset($request->crosstrain)) {
            $options['crosstrain'] = intval($request->crosstrain);
        }
        if ($options['crosstrain'] < 2) {
            $options['crosstrain'] = 0;
        }
        if ($options['crosstrain'] > 10000) {
            $options['crosstrain'] = 10000;
        }

        $options['reset_after'] = 0;
        if (isset($request->reset_after)) {
            $options['reset_after'] = intval($request->reset_after);
        }
        if ($options['reset_after'] < 100) {
            $options['reset_after'] = 0;
        }
        if ($options['reset_after'] > 10000) {
            $options['reset_after'] = 10000;
        }

        $options['indicator_class'] = \Config::get('GTrader.FannTraining.indicator.class');
        $options['indicator_params'] = \Config::get('GTrader.FannTraining.indicator.params');
        if (isset($request->maximize_for)) {
            $available = \Config::get('GTrader.FannTraining.indicators');
            foreach ($available as $ind) {
                if ($ind['name'] == $request->maximize_for)  {
                    $options['indicator_class'] = $ind['class'];
                    $options['indicator_params'] = $ind['params'];
                    break;
                }
            }
        }

        $strategy->setParam(
            'last_training',
            array_merge([
                'exchange' => $exchange,
                'symbol' => $symbol,
                'resolution' => $resolution,
            ], $options))
            ->save();

        $training = FannTraining::firstOrNew([
            'strategy_id'   => $strategy_id,
            'status'        => 'training'
        ]);

        $training->strategy_id = $strategy_id;
        $training->status = 'training';
        $training->exchange_id = $exchange_id;
        $training->symbol_id = $symbol_id;
        $training->resolution = $resolution;
        $training->options = $options;
        $training->progress = [];

        $from_scratch = !$strategy->hasBeenTrained();
        if (isset($request->from_scratch)) {
            if (intval($request->from_scratch)) {
                $from_scratch = true;
            }
        }

        if ($from_scratch) {
            error_log('Training from scratch.');
            $strategy->destroyFann();
            $strategy->deleteFiles();
            $strategy->loadOrCreateFann();
            $strategy->deleteHistory();
        } else {
            $last_epoch = $strategy->getLastTrainingEpoch();
            error_log('Continuing training from epoch '.$last_epoch);
            //$training->setProgress('epoch', $last_epoch);
            $training->progress = ['epoch' => $last_epoch];
        }

        $training->save();

        $html = view('Strategies/FannTrainProgress', [
            'strategy' => $strategy,
            'training' => $training
        ]);

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
        $progress = $training->progress;
        if (!is_array($progress)) {
            $progress = [];
        }
        foreach (['test', 'test_max', 'verify', 'verify_max'] as $field) {
            $value = isset($progress[$field]) ? $progress[$field] : 0;
            $progress[$field] = number_format(floatval($value), 2, '.', '');
        }
        return response(json_encode($progress), 200);
    }


    public function trainHistory(Request $request)
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

        return response($strategy->getHistoryPlot($request->width, $request->height), 200);
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
        $html = view('Strategies/FannTrainProgress', [
            'strategy' => $strategy,
            'training' => $training
        ]);
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
        $html = view('Strategies/FannTrainProgress', [
            'strategy' => $strategy,
            'training' => $training
        ]);
        return response($html, 200);
    }
}
