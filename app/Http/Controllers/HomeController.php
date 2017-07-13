<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use GTrader\Page;
use GTrader\Exchange;
use GTrader\Chart;
use GTrader\Series;
use GTrader\Strategy;
//use GTrader\Indicator;
use GTrader\Util;
use GTrader\Bot;


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
        Page::add('scripts_bottom', '<script src="/js/Mainchart.js"></script>');
        $chart->addPageElements();

        Page::add('stylesheets', '<link href="/css/nouislider.min.css" rel="stylesheet">');
        Page::add('scripts_bottom', '<script src="/js/nouislider.min.js"></script>');

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





    public function test()
    {
/*
        $t0 = time();
        $m0 = memory_get_usage();
        $candlesOl = new Series([
            'start' => 1,
            'end' => $t0,
            'limit' => 0,
            'resolution' => 300,
        ]);

        $candlesOl->reset();
        dump($candlesOl->next());
        echo 'CandlesOl: '.$candlesOl->size().
            ' Mem: '.Util::humanBytes(memory_get_usage() - $m0).
            ' Time: '.(time() - $t0).'s';
        //dump($candlesOl->getParams());
*/
/*
        DB::listen(function ($query) {

            $replace = function ($sql, $bindings) {
                $needle = '?';
                foreach ($bindings as $replace) {
                    $pos = strpos($sql, $needle);
                    if ($pos !== false) {
                        $sql = substr_replace($sql, "'".$replace."'", $pos, strlen($needle));
                    }
                }
                return $sql;
            };
            $sql = $replace($query->sql, $query->bindings);
            dump($sql);
        });
*/

        $t1 = time();
        $m1 = memory_get_usage();
        $candles = new Series([
            'start' => 1,
            'end' => $t1,
            'limit' => 0,
            'resolution' => 300,
        ]);

        $candles->reset();
        dump($candles->next());
        return 'Candles: '.$candles->size().
            ' Mem: '.Util::humanBytes(memory_get_usage() - $m1).
            ' Time: '.(time() - $t1).'s';




        $functions = get_defined_functions();
        $functions_list = [];
        foreach ($functions['internal'] as $func) {
            if (!strstr($func, 'trader_')) {
                continue;
            }
            $f = new \ReflectionFunction($func);
            $args = [];
            foreach ($f->getParameters() as $param) {
                $tmparg = '';
                if ($param->isOptional()) {
                    $tmparg .= '[';
                }
                if ($param->isPassedByReference()) {
                    $tmparg .= '&';
                }
                $tmparg .= '$'.$param->getName();
                if ($param->isDefaultValueAvailable()) {
                    $tmparg .= ' = '.$param->getDefaultValue();
                }
                if ($param->isOptional()) {
                    $tmparg .= ']';
                }
                $args[] = $tmparg;
            }
            $functions_list[] = $func.' ('.implode(', ', $args).')';
        }
        dd($functions_list);



        $arr = [
            'a' => [
                'b' => [
                    'c' => [
                        'v1',
                        ['d' => 'i1'],
                        ['1' => 's1'],
                    ]
                ]
            ]
        ];

        $list = ['a', 'b', 'c', 1, 'd'];

        $key = 'a.b.c.1';
        $res = Arr::get($arr, $key);

        return view('basic')->with([
            'content' => json_encode($res),
        ]);

        $count = 100000;
        $baseline = memory_get_usage();

        for ($i=0; $i<$count; $i++) {
            $key = bin2hex(random_bytes(50));
            $max = getrandmax();
            $val = (rand(0, $max) - ($max / 2)) / $max / 2 ;
            $a[] = [$key => $val];
        }
        return view('basic')->with([
            'content' =>
                $count.' pieces of random key-value pairs like <code>["'.
                $key.'" => '.$val.']</code> use approx. '.
                Util::humanBytes(memory_get_usage() - $baseline)
        ]);
    }
}
