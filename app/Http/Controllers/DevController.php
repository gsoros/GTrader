<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;
use GTrader\Page;
use GTrader\Exchange;
use GTrader\Chart;
use GTrader\Series;
use GTrader\Strategy;
use GTrader\Indicator;
use GTrader\Util;
use GTrader\Bot;
use GTrader\Rand;
use GTrader\Log;
use GTrader\Event;
use GTrader\DevUtil;

class DevController extends Controller
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


    public function dev(Request $request)
    {
        return view('devIndex');
    }


    public function vis(Request $request)
    {
        return view('vis');
    }


    public function json(Request $request)
    {
        if (!($file = isset($request->file) ? $request->file : null)) {
            return response('file not found', 404);
        }
        if (!file_exists(storage_path('dumps/'.$file))) {
            return response('file not found', 404);
        }
        $file = storage_path('dumps/'.$file);
        if (!is_readable($file)) {
            return response('file not readable', 403);
        }
        //Log::debug('dev@json: '.$file);
        return response(file_get_contents($file), 200);
    }


    public function dump(Request $request)
    {

        $user = Auth::user();

        foreach ($user->strategies() as $s) {
            if (51 == $s->getParam('id')) {
                dd($s->getParams());
                $sig = $s->getSignalsIndicator();
                dd($sig, $sig->nesting());
            }
        }

        ob_start();
        echo 'Charts:';
        foreach ($user->charts() as $chart) {
            dump($chart);
            echo '<a class="btn btn-primary btn-mini" href="/chart.delete?name='.$chart->getParam('name').'">Delete</a><br>';
        }

        echo '<hr>Strategies:';
        dump(array_map(function($strategy) {
            if (!method_exists($strategy, 'getTrainingClass')) {
                return $strategy;
            }
            return $strategy->setParam(
                'trainings',
                $strategy->getTrainingClass()::where(
                    'strategy_id',
                    $strategy->getParam('id')
                )->get()->toArray()
            );
        }, $user->strategies()));

        echo '<hr>User:';
        dump($user);

        echo '<hr>Bots:';
        dump($user->bots);

        echo '<hr>Trades:';
        dump($user->trades->toArray());

        echo '<hr>ExchangeConfigs:';
        dump($user->exchangeConfigs->toArray());

        echo '<hr>Session:';
        dump($request->session()->all());

        return view('basic')->with([
            'content' => ob_get_clean(),
        ]);
    }



    public function test(Request $request)
    {
        switch ($request->mode) {

            case 'mutate':

                echo '<link href="/css/diff.css" rel="stylesheet">';
                //DB::enableQueryLog();

                /*
                $s1 = Strategy::load(
                    DB::table('strategies')
                    ->select('id')
                    ->where('id', 55)
                    ->first()
                    ->id
                );

                $candles = new Series([
                    'exchange' => Exchange::getNameById($beagle->exchange_id),
                    'symbol' => Exchange::getSymbolNameById($beagle->symbol_id),
                    'resolution' => $beagle->resolution,
                    'start' => $beagle->options['test_start'],
                    'end' => $beagle->options['test_end'],
                    'limit' => 0
                ]);

                $candles->first(); // load
                $s1->setCandles($candles);
                $candles->setStrategy($s1);

                //foreach($s1->getAvailableSources() as $sig => $label) {
                //    dump(\GTrader\Indicator::decodeSignature($sig));
                //}

                $s2 = clone $s1;
                $s2->setMutationRate(.5);
                $s2->mutate();
                $ind = $s1->getSignalsIndicator();
                $mut = $s2->getSignalsIndicator();
                //dump($signals);

                /*
                $sig = array_keys($strat->getAvailableSources())[0];
                $ind_arr = Indicator::decodeSignature($sig);
                dump($ind_arr);

                $ind = Indicator::make($ind_arr['class'], $ind_arr['params']);
                dump($ind);
                */
                //dump($ind, $mut);

                /* echo DevUtil::diff(
                    $ind->getSignature(null, JSON_PRETTY_PRINT),
                    $mut->getSignature(null, JSON_PRETTY_PRINT)
                );
                die; */


                //DB::table('strategies')->where('name', 'like', 'evolving_%')->delete();
                if (!$beagle = \GTrader\Strategies\Beagle::first()) {
                    dd('No Beagle');
                }
                if (!$beagle = $beagle->get()[0]) {
                    dd('No Beagle');
                }
                //dd($beagle->options);
                if (!$father = $beagle->loadStrategy()) {
                    dd('No Strat');
                }
                //dd($father);
                $father->setParam('mutation_rate', .5);
                $father->setParam('max_nesting', 3);

                $candles = new Series([
                    'exchange' => Exchange::getNameById($beagle->exchange_id),
                    'symbol' => Exchange::getSymbolNameById($beagle->symbol_id),
                    'resolution' => $beagle->resolution,
                    'start' => $beagle->options['test_start'],
                    'end' => $beagle->options['test_end'],
                    'limit' => 0
                ]);
                $candles->first(); // load

                //dump('O: '.$candles->count());
                $father->setCandles($candles);
                $beagle->father($father);
                $beagle->evaluate($father);


                //$og_candles = $father->getCandles()->replicate();
                $og_candles = clone $father->getCandles();
                //dump('OG: '.$og_candles->debug());
                for ($i=0; $i<10; $i++) {
                    for ($j=0; $j<10; $j++) {
                        $son = clone $father;
                        $son->mutate();
                        $beagle->evaluate($son);
                        $son->kill();
                    }
                    //dump($father->getCandles()->debug());

                    echo \GTrader\Util::getMemoryUsage().' ('.gc_collect_cycles().') ';
                    flush();
                }
                dump('OG: '.$candles->debug());
                dd($candles);
                dd($father, $son);

                //$candles->setStrategy($father);
                dump($father->getCandles()->getOrAddIndicator($beagle->getMaximizeSig($father))->getLastValue());

                //$candles2 = clone $candles;
                //dump('O2: '.$candles2->count());

                //dd($candles->getIndicators());
                //dd($candles->getOrAddIndicator($beagle->getMaximizeSig($father))->getLastValue());

                for ($i = 0; $i < 10; $i++) {
                    $son = clone($father);
                    $son->mutate();
                    $son->setCandles($father->getCandles()); // works
                    //$son->setCandles($candles2); //works nots
                    //$cached = $son->cached('signals_indicator');
                    //$son->unCache('signals_indicator');
                    //$son->unCache('maximize_sig');
                    //$candles2->setStrategy($son);
                    $sig = $beagle->getMaximizeSig($son); //dump(md5($sig));
                    $ind = $son->getCandles()->getOrAddIndicator($sig);
                    $bal = $ind->calculated(false)->getLastValue();
                    dump($i.': '.$bal);
                    //dump($i.': '.$bal.' '.md5($max_sig).' '.$cached);
                    //if (true) {
                    if (!$bal) {
                        //dump($son);
                    }
                }
                die;

                $test = function ($strat) use ($beagle, $candles)
                {
                    dump('B: '.$candles->count());
                    $strat->setCandles($candles);
                    $candles->setStrategy($strat);
                    return $candles
                        ->getOrAddIndicator($beagle->getMaximizeSig($strat))
                        ->getLastValue();
                };

                // $s->setMutationRate($beagle->options['mutation_rate']);

                $father_bal = $test($father);
                $father_sig = $father->getSignalsIndicator()->getSignature(null, JSON_PRETTY_PRINT);




                $generation = $balances = [];

                for ($i = 0; $i < 10; $i++) {
                    $uid = \GTrader\Rand::uniqId();
                    //echo $i.' '.$uid.' '.(\GTrader\Util::getMemoryUsage()).' '; flush();
                    set_time_limit(25);

                    $generation[$uid] = clone $father;

                    $generation[$uid]->mutate();

                    $bal = $test($generation[$uid]);

                    dump('A: '.$candles->count());

                    $generation[$uid]->setFitness($bal);
                    $balances[$uid] = $bal;

                }
                dump(\GTrader\Util::getMemoryUsage());
                arsort($balances);
                dump($balances);
                reset($balances);
                $fittest = key($balances);
                $best_fitness = array_shift($balances);
                if (true) {//($father_bal <= $best_fitness) {
                    //$generation[$fittest]->getCandles()->purgeIndicators();
                    //$generation[$fittest]->purgeIndicators();
                    $generation[$fittest]
                        ->setParams($father->getParams())
                        ->setParam('fitness', $best_fitness);
                    //    ->save();
                    if (true) {//($father_bal < $best_fitness) {
                        dump('New champ:');
                        $new_sig = $generation[$fittest]->getSignalsIndicator()->getSignature(null, JSON_PRETTY_PRINT);
                        echo DevUtil::diff(
                            $father_sig,
                            $new_sig
                        );
                        //dump($new_sig, $generation[$fittest]);
                        //dump(Indicator::decodeSignature($new_sig));
                        //break;
                        dd($candles->getIndicators());
                    }
                }

                /* ?><script>setTimeout(function() { window.location.reload(); }, 0);</script><?php */

                break;

            case 'form':
                $f = new \GTrader\Form([
                    'some_select' => [
                        'type' => 'select',
                        'options' => [
                            'opt_a_k' => 'opt_a_v',
                            'opt_b_k' => 'opt_b_v',
                        ],
                        'class' => 'aclass',
                    ],
                ], [
                    'some_select' => 'opt_b_k',
                ]);
                dump($f->toHtml());
                break;

            case 'dist':
                $tests = [
                    'floatNormal' => [
                        'samples' => 5000,
                        'tests' => [
                            // min, max, peak, weight
                            [0, 1000, 500, .5],
                            [0, 1000, 200, 1],
                            [0, 1000, 200, .99],
                            [0, 1000, 200, 0],
                            [0, 1000, 200, .01],
                            [0, 1000, 800, .3],
                            [1000, 0, 200, .6],
                            [0, 1000, 1000, .75],
                            [0, 1000, 1000, .0001],
                            [0, 1, 1, 0.01],
                        ],
                        'callback' => function($input) {
                            return Rand::floatNormal($input[0], $input[1], $input[2], $input[3]);
                        },
                    ],
                    'pickNormal' => [
                        'samples' => 5000,
                        'tests' => [
                            // items, default, weight
                            [range(1, 100), 20, .5],
                            [range(1, 100), 20, .01],
                            [range(1, 100), 20, .99],
                        ],
                        'callback' => function($input) {
                            return Rand::pickNormal($input[0], $input[1], $input[2]);
                        },
                    ],
                ];
                $width = 1200;

                function test($callback, $input, $samples, $width) {
                    $start = microtime(true);
                    $sum = null;
                    $vals = [];
                    //$step = ($) $width / 2;
                    for ($i = 1; $i <= $samples; $i++) {
                        $val = $callback($input);
                        // $min = is_null($min) ? $val : min($min, $val);
                        // $max = is_null($max) ? $val : max($max, $val);
                        $sum += $val;
                        // $int = round($val);
                        // $vals[$int] = isset($vals[$int]) ? $vals[$int] + 1 : 1;
                        $vals[] = $val;
                    }
                    sort($vals);
                    $raw = $vals;
                    $min = min($vals);
                    $max = max($vals);
                    $step = ($max - $min) / $width * 2;
                    $dist = [];
                    $key = $min;
                    $dist_key = strval(round($key, 2));
                    while ($val = array_shift($vals)) {
                        if ($val >= $key + $step) {
                            while ($key < $val) {
                                $key += $step;
                            }
                            $dist_key = strval(round($key, 2));
                        }
                        if (!isset($dist[$dist_key])) {
                            $dist[$dist_key] = 0;
                        }
                        $dist[$dist_key]++;
                    }
                    dump([
                        'in' => array_merge(['samples' => $samples], $input),
                        'out' => [
                            't' => microtime(true) - $start,
                            'min' => number_format($min, 2),
                            'max' => number_format($max, 2),
                            'avg' => number_format($sum / $samples, 2),
                            //'raw' => $raw,
                            'dist' => $dist,
                        ],
                    ]);
                    $plot = new \GTrader\Plot ([
                        'name' => 'Plot',
                        'width' => $width,
                        'height' => 200,
                        'data' => [
                            'distribution' => [
                                'values' => $dist
                            ]
                        ],
                    ]);
                    echo $plot->toHtml();
                }

                foreach ($tests as $name => $test) {
                    dump($name);
                    foreach ($test['tests'] as $inputs) {
                        test($test['callback'], $inputs, $test['samples'], $width);
                    }
                }
                break;

            default:
            ?><a href='?mode=mutate'>Mutate</a> <?php
            ?><a href='?mode=form'>Form</a> <?php
            ?><a href='?mode=dist'>Dist</a> <?php
        }
    }


    public function phpinfo(Request $request)
    {
            ob_start();
            phpinfo();
            $info = ob_get_contents();
            ob_end_clean();
            return $info;
    }
}
