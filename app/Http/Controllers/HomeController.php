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
            'debug'             => var_export($chart->getCandles()->getParams(), true)
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
            return $strategy->setParam(
                'trainings',
                \GTrader\FannTraining::where('strategy_id', $strategy->getParam('id'))
                    ->get()
                    ->toArray()
            );
        }, $user->strategies()));

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



    public function test()
    {
        function tester($arr) {
            $max_tries = 5000;
            $start = microtime(true);
            $min = $max = $sum = null;
            $vals = [];
            for ($i = 1; $i <= $max_tries; $i++) {
                $val = Util::randomFloatWeighted($arr[0], $arr[1], $arr[2], $arr[3]);
                $min = is_null($min) ? $val : min($min, $val);
                $max = is_null($max) ? $val : max($max, $val);
                $sum += $val;
                $int = round($val);
                $vals[$int] = isset($vals[$int]) ? $vals[$int] + 1 : 1;
            }
            dump([
                'params' => 'tries: '.$max_tries.', input: '.join(', ', $arr),
                't' => microtime(true) - $start,
                'min' => number_format($min, 2),
                'max' => number_format($max, 2),
                'avg' => number_format($sum / ($i - 1), 2),
                'vals' => $vals,
            ]);
            ksort($vals);
            $plot = new \GTrader\Plot ([
                'name' => 'Plot',
                'width' => 1200,
                'height' => 200,
                'data' => ['distribution' => ['values' => $vals]],
            ]);
            echo $plot->toHtml();

        }
        foreach ([
            // min, max, peak, weight
            [0, 1000, 500, .5],
            [0, 1000, 200, 1],
            [0, 1000, 200, .9],
            [0, 1000, 200, 0],
            [0, 1000, 200, .01],
            [0, 1000, 800, .3],
            [1000, 0, 200, .6],
            [0, 1000, 1000, .75],
            [0, 1000, 1000, .0001],
            [0, 1, 1, 0.01],
        ] as $arr) {
            tester($arr);
        }
        exit;
    }
}
