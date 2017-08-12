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
use GTrader\Chart;
use GTrader\Plot;
use GTrader\Log;

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
            [
                'id' => 'new',
                'user_id' => $user_id,
                'name' => $name,
                'description' => $request->strategyClass.' strategy'
            ]
        );
        $strategy->save();
        $form = $strategy->toHTML();
        return response($form, 200);
    }


    public function form(Request $request)
    {
        if (([$error, $strategy_id, $strategy] =
            $this->getStrategy($request))[0]) {
            return $error;
        }
        $form = $strategy->toHTML();
        return response($form, 200);
    }


    public function delete(Request $request)
    {
        if (([$error, $strategy_id, $strategy] =
            $this->getStrategy($request))[0]) {
            return $error;
        }
        $strategy->delete();
        return response(Strategy::getListOfUser(Auth::id()), 200);
    }


    public function save(Request $request)
    {
        if (([$error, $strategy_id, $strategy] =
            $this->getStrategy($request))[0]) {
            return $error;
        }
        $strategy->handleSaveRequest($request);
        $strategy->save();
        return response(Strategy::getListOfUser(Auth::id()), 200);
    }


    public function train(Request $request)
    {
        if (([$error, $strategy_id, $strategy] =
            $this->getStrategy($request))[0]) {
            return $error;
        }
        $training_class = $strategy->getTrainingClass();
        $training = $training_class::where('strategy_id', $strategy_id)
            ->where(function ($query) {
                $query->where('status', 'training')
                    ->orWhere('status', 'paused');
            })
            ->first();
        if (is_object($training)) {
            $html = view('TrainingProgress', [
                'strategy' => $strategy,
                'training' => $training
            ]);
            return response($html, 200);
        }

        $training = $training_class::firstOrNew([
            'strategy_id' => $strategy_id,
        ]);
        if (!$html = $training->toHtml()) {
            Log::error('Could not display training form');
            return response('Error displaying training form', 400);
        }
        return response($html, 200);
    }


    public function trainStart(Request $request)
    {
        Log::debug($request->all());

        if (([$error, $strategy_id, $strategy] =
            $this->getStrategy($request))[0]) {
            return $error;
        }

        $training = $strategy->getTrainingClass()::firstOrNew([
            'strategy_id' => $strategy_id
        ]);

        return $training->handleStartRequest($request);
    }


    public function trainStop(Request $request)
    {
        if (([$error, $strategy_id, $strategy] =
            $this->getStrategy($request))[0]) {
            return $error;
        }
        $training_class = $strategy->getTrainingClass();
        $training = $training_class::where('strategy_id', $strategy_id)
            ->where(function ($query) {
                $query->where('status', 'training')
                    ->orWhere('status', 'paused');
            })->first();
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
        if (([$error, $strategy_id, $strategy] =
            $this->getStrategy($request))[0]) {
            return $error;
        }
        $training_class = $strategy->getTrainingClass();
        $training = $training_class::where('strategy_id', $strategy_id)
            ->where('status', 'training')->first();
        if (!is_object($training)) {
            Log::error('Training not found for strategy '.$strategy_id);
            return response('Training not found.', 404);
        }
        $progress = $training->progress;
        if (!is_array($progress)) {
            $progress = [];
        }
        foreach (['test', 'test_max', 'verify', 'verify_max'] as $field) {
            $value = $progress[$field] ?? 0;
            $progress[$field] = number_format(floatval($value), 2, '.', '');
        }
        return response(json_encode($progress), 200);
    }


    public function trainHistory(Request $request)
    {
        if (([$error, $strategy_id, $strategy] =
            $this->getStrategy($request))[0]) {
            return $error;
        }

        return response(
            $strategy->getHistoryPlot($request->width, $request->height),
            200
        );
    }


    public function trainPause(Request $request)
    {
        if (([$error, $strategy_id, $strategy] =
            $this->getStrategy($request))[0]) {
            return $error;
        }
        $training = $strategy->getTrainingClass()::where('strategy_id', $strategy_id)
            ->where('status', 'training')->first();
        if (!is_object($training)) {
            Log::error('Training not found for strategy '.$strategy_id);
            return response('Training not found.', 404);
        }
        $training->status = 'paused';
        $training->save();
        $html = view('TrainingProgress', [
            'strategy' => $strategy,
            'training' => $training
        ]);
        return response($html, 200);
    }


    public function trainResume(Request $request)
    {
        if (([$error, $strategy_id, $strategy] =
            $this->getStrategy($request))[0]) {
            return $error;
        }
        $training = $strategy->getTrainingClass()::where('strategy_id', $strategy_id)
            ->where('status', 'paused')->first();
        if (!is_object($training)) {
            Log::error('Training not found for strategy '.$strategy_id);
            return response('Training not found.', 404);
        }
        $training->status = 'training';
        $training->save();
        $html = view('TrainingProgress', [
            'strategy' => $strategy,
            'training' => $training
        ]);
        return response($html, 200);
    }


    public function sample(Request $request)
    {
        if (! $chart = Chart::loadFromSession($request->chart)) {
            Log::error('No chart in session');
            return response('Chart not found.', 200);
        }
        if (!($strategy = $chart->getStrategy())) {
            Log::error('Failed to get strategy ');
            return response('Select a FANN strategy first.', 200);
        }
        if (!$strategy->isClass('GTrader\\Strategies\\Fann')) {
            Log::error('Not a fann strategy');
            return response('Select a FANN strategy first.', 200);
        }
        if (!($candles = $chart->getCandles())) {
            Log::error('Failed to get the series ');
            return response('Could not get the series.', 200);
        }

        $request->t += 1;
        $resolution = $candles->getParam('resolution');
        $sample_size = $strategy->getParam('sample_size');
        $target_distance = $strategy->getParam('target_distance');
        //$t = $request->t - $resolution * $sample_size;

        $limit = $sample_size + $target_distance + 200;
        $candles = new Series([
            'exchange' => $candles->getParam('exchange'),
            'symbol' => $candles->getParam('symbol'),
            'resolution' => $resolution,
            'limit' => $limit,
            'end' => $request->t + $target_distance * $resolution,
        ]);
        $strategy->setCandles($candles);
        $candles->setStrategy($strategy);

        $width = abs(floor($request->width))-30;
        $html = $strategy->getSamplePlot($width, floor($width / 2), $request->t);

        return response($html, 200);
    }


    public function simpleSignalsForm(Request $request)
    {
        if (([$error, $strategy_id, $strategy] =
            $this->getStrategy($request))[0]) {
            return $error;
        }
        if (!$strategy->isClass('GTrader\\Strategies\\Simple')) {
            Log::error('Not a Simple strategy '.$strategy_id);
            return response('Failed to load strategy.', 403);
        }
        $form = $strategy->viewSignalsForm();
        return response($form, 200);
    }


    /**
     * Get strategy from the request
     * @param  Request $request
     * @return array   [Response error, null, null] | [null, int strategy_id, Strategy]
     */
    protected function getStrategy(Request $request): array
    {
        $strategy_id = intval($request->id);
        if (!($strategy = Strategy::load($strategy_id))) {
            Log::error('Failed to load strategy ID '.$strategy_id);
            return [response('Strategy not found.', 404), null, null];
        }
        if ($strategy->getParam('user_id') !== Auth::id()) {
            Log::error('That strategy belongs to someone else: ID '.$strategy_id);
            return [response('Strategy not found.', 403), null, null];
        }
        return [null, $strategy_id, $strategy];
    }
}
