<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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


class Debug extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Helps get rid of bugs.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ('local' !== \Config::get('app.env')) {
            $msg = 'This command cannot be called in a non-local environment.';
            dump($msg);
            Log::error($msg);
            return;
        }
        return $this->debug();
    }


    protected function debug()
    {


        $mem = function($msg = '') {
            dump(
                ($msg ? $msg.' ' : '').
                gc_mem_caches().' '.
                gc_collect_cycles().' '.
                \GTrader\Util::getMemoryUsage());
        };



        $mem('start');

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
        $father->setParam('mutation_rate', .99);
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
        //Event::dumpSubscriptions();
        $father->setCandles($candles);
        $beagle->father($father);
        $beagle->evaluate($father);




        $mem('before');
        for ($i=0; $i<1; $i++) {
            //$sig = Indicator::make('Ema')->mutate(.5, 3)->getSignature();
            //$ind = $father->getOrAddIndicator($sig);
            //Log::debug('before');
            //$father->unsetIndicator($ind);
            //Log::debug('after');

            $son = clone $father;
            $son->mutate();
            $beagle->evaluate($son);
            $son->setParam('name', 'son '.$i)->visualize(15);
            //$son->kill();

        }

        //$son->setParam('name', 'son')->visualize(15);
        $father->setParam('name', 'father')->visualize(15);

        DevUtil::fdump($father->visGetJSON(), storage_path('dumps/debug.json'));

        //dd(\GTrader\Store::singleton());

        unset($son);
        $mem('after');
        $father->kill();
        unset($father);
        $candles->kill();
        unset($candles);
        unset($beagle);
        $mem('end');
        //DevUtil::memdump();
        //dump(Event::subscriptionCount());
        Event::dumpSubscriptions();
        //Event::clearSubscriptions();
        die;
    }
}
