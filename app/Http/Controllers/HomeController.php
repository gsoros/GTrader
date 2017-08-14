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
//use GTrader\Indicator;
use GTrader\Util;
use GTrader\Bot;
use GTrader\Rand;
use GTrader\Log;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        if (! $this->checkDB()) {
            dd('Could not connect to the database. Check your DB settings in .env');
        }
        $this->middleware('auth');
    }


    protected function checkDB()
    {
        $users = 0;
        $max_tries = 10;
        $tries = 0;
        $delay = 3;
        while ($tries <= $max_tries && !$users) {
            $tries++;
            try {
                if ($users = DB::table('users')->count()) {
                    return true;
                }
                $this->migrateAndSeed();
            } catch (\Exception $e) {
                try {
                    $this->migrateAndSeed();
                } catch (\Exception $f) {
                    echo 'Automigrate attempt '.$tries.' failed<br>';
                    flush();
                }
            }
            if ($tries < $max_tries) {
                sleep($delay);
            }
        }
        return false;
    }

    protected function migrateAndSeed()
    {
        Artisan::call('migrate', ['--path' => 'database/migrations']);
        Artisan::call('db:seed');
    }



    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard(Request $request)
    {
        $chart = Chart::load(Auth::id(), 'mainchart', null, [
            'autorefresh' => true,
            //'disabled' => ['map'],
            'indicators_if_new' => [
                'Ohlc',
                'Vol',
            ],
        ]);
        Page::add('scripts_top', '<script src="/js/GTrader.js"></script>');
        $chart->addPageElements();
        Page::add('scripts_bottom', '<script src="/js/Mainchart.js"></script>');

        Page::add('stylesheets', '<link href="/css/nouislider.min.css" rel="stylesheet">');
        Page::add('scripts_bottom', '<script src="/js/nouislider.min.js"></script>');

        //Page::add('stylesheets', '<link href="/css/bootstrap-combobox.css" rel="stylesheet">');
        //Page::add('scripts_bottom', '<script src="/js/bootstrap-combobox.js"></script>');

        $viewData = [
            'chart'             => $chart->toHtml(),
            'strategies'        => Strategy::getListOfUser(Auth::id()),
            'exchanges'         => Exchange::getList(),
            'bots'              => Bot::getListOfUser(Auth::id()),
            'stylesheets'       => Page::get('stylesheets'),
            'scripts_top'       => Page::get('scripts_top'),
            'scripts_bottom'    => Page::get('scripts_bottom'),
            'debug'             => null, //var_export($chart->getCandles()->getParams(), true),
        ];

        $chart->saveToSession()->save();

        return view('dashboard')->with($viewData);
    }


    public function dump(Request $request)
    {
        $user = Auth::user();

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
                DB::table('strategies')->where('name', 'like', 'evolving_%')->delete();
                if (!$beagle = \GTrader\Strategies\Beagle::first()->get()[0]) {
                    dd('No Beagle');
                }
                //dump($b);
                if (!$father = $beagle->loadStrategy()) {
                    dd('No Strat');
                }

                $test = function ($strat) use ($beagle)
                {
                    $candles = $strat->getCandles();
                    $sig = $beagle->getMaximizeSig($strat);
                    if (!($ind = $candles->getOrAddIndicator($sig))) {
                        Log::error('Could not getOrAddIndicator() for '.$type);
                        return 0;
                    }
                    $ind->addRef('root');
                    return $ind->getLastValue(true);
                };

                // $s->setMutationRate($beagle->options['mutation_rate']);
                $father->setMutationRate(.9999);
                dump($father->getSignalsIndicator()->getParam('indicator'),
                    ($father_bal = $test($father)));

                \GTrader\Event::subscribe('indicator.change', function($o, $e) {
                    // static $events = 0;
                    // echo 'events: '.($events++).' ';
                    // dump('change event', $o, $e);
                });

                // $c = clone $s;
                // dd($s, $c);

                // $class = get_class($father);
                // $params = $father->getParams();
                // $sigs = [];
                // foreach ($father->getIndicators() as $ind) {
                //     if (method_exists($ind, 'subscribeEvents')) {
                //         $ind->subscribeEvents(false);
                //     }
                // }
                //     if ('Signals' !== $ind->getShortClass()) {
                //         $sigs[] = $ind->getSignature();
                //     }
                // }
                // $father->killIndicators();
                // $father->kill();
                // unset($father);

                $generation = $balances = [];

                for ($i = 0; $i < 100; $i++) {
                    $uid = \GTrader\Rand::uniqId();
                    //echo $i.' '.$uid.' '.(\GTrader\Util::getMemoryUsage()).' '; flush();
                    set_time_limit(15);

                    $generation[$uid] = clone $father;

                    // $c = $class::make();
                    // $c->setParams($params);
                    // foreach ($sigs as $sig) {
                    //     $c->addIndicatorBySignature($sig);
                    // }


                    $generation[$uid]->mutate();

                    //dd($generation[$uid]->getSignalsIndicator()->getParam('indicator'));

                    //$generation[$uid]->setParam('id', 'new');
                    //$generation[$uid]->setParam('name', 'evolving_'.$beagle->id.'_'.$uid);

                    //dd($c->getAvailableSources());
                    // dump('Ema len: '.($generation[$uid]->getFirstIndicatorByClass('Ema')->getParam('indicator.length')));
                    // $sig = $generation[$uid]->getSignalsIndicator()->getParam('indicator.input_long_a');
                    // if (!strstr($sig, '{"class":"Ema"')) {
                    //     dump('Sig in: '.$sig);
                    // }

                    $bal = $test($generation[$uid]);
                    //dump('Bal: '.$bal);
                    $generation[$uid]->setFitness($bal);
                    $balances[$uid] = $bal;

                    // $c->killIndicators();
                    // $c->kill();
                    // unset($c);
                    //echo 'EvSubs: '.\GTrader\Event::subscriptionCount().'<br/>';
                }
                dump(\GTrader\Util::getMemoryUsage());
                arsort($balances);
                dump($balances);
                reset($balances);
                $fittest = key($balances);
                if ($father_bal < ($fitness = array_shift($balances))) {
                    dump('Saving fittest: ', $generation[$fittest]->getSignalsIndicator()->getParam('indicator'));
                    $generation[$fittest]
                        ->setParams($father->getParams())
                        ->setParam('fitness', $fitness)
                        ->save();
                }
                ?><script>setTimeout(function() { window.location.reload(); }, 0);</script><?php
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
}
