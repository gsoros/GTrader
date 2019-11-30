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
                    Log::error($e->getMessage());
                    $this->migrateAndSeed();
                } catch (\Exception $f) {
                    echo 'Automigrate attempt '.$tries.' failed<br>';
                    Log::error($f->getMessage());
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
            'height' => 200,
            'heightPercentage' => 100,
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

        if (app()->environment('local')) {
            Page::add('scripts_top', '<script src="/js/vis-network.min.js"></script>');
            Page::add('stylesheets', '<link href="/css/vis-network.min.css" rel="stylesheet">');
        }

        $viewData = [
            'chart'             => $chart->toHtml(),
            'strategies'        => Strategy::getListOfUser(Auth::id()),
            'exchanges'         => Exchange::getList([
                'get' => ['self', 'configured'],
                'user_id' => Auth::id(),
            ]),
            'bots'              => Bot::getListOfUser(Auth::id()),
            'dev'               =>
                app()->environment('local') ? view('Dev/index') : '',
            'stylesheets'       => Page::get('stylesheets'),
            'scripts_top'       => Page::get('scripts_top'),
            'scripts_bottom'    => Page::get('scripts_bottom'),
        ];

        $chart->saveToSession()->save();

        return view('dashboard')->with($viewData);
    }
}
