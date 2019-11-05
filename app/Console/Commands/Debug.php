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
        if ('local' !== config('app.env')) {
            $msg = 'This command cannot be called in a non-local environment.';
            dump($msg);
            Log::error($msg);
            return;
        }
        return $this->debug();
    }


    protected function debug()
    {
        $ref = $nor = 0;
        for ($i=0; $i<10; $i++) {
            echo $i;
            $m = microtime(true);
            $array = range(1, 100000);
            foreach ($array as $k => $v) {
                $array[$k] = 1;
            }
            $nor += microtime(true) - $m;

            $m = microtime(true);
            $array = range(1, 100000);
            foreach ($array as &$v) {
                $v = 1;
            }
            $ref += microtime(true) - $m;
        }
        echo PHP_EOL.$nor.' '.$ref.PHP_EOL;
        die;


        for ($i = 10; --$i;) {
            Log::error('Error test', ['key' => [1, 2, 3, 4, 100 => 'haha']]);
        }
        Log::sparse('Sparse test'."\r\n");
        Log::sparse('Sparse test');
        Log::sparse('Sparse test');
        Log::debug('Sparse test');
        Log::sparse('Sparse test');
        die;

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
        //Event::dumpSubscriptions();
        $father->setCandles($candles);
        $beagle->father($father);
        $beagle->evaluate($father);

        //Event::dumpSubscriptions();
        //$s = [];
        //foreach ($father->getIndicators() as $i)
        //    $s[] = $i->oid();
        //dump(join(', ', $s));
        //$father->mutate();
        //die;
        dump('Father fitness: '.$father->fitness());

        $dump_foreign = function($strat) {
            $owners = [];
            foreach ($strat->getIndicators() as $ind) {
                if ($ind->getOwner() !== $strat) {
                    $owners[$ind->oid()] = $ind->getOwner()->oid();
                }
            }
            if (count($owners)) {
                dump('Foreign indicators for '.$strat->oid(), $owners);
            }
        };
        //$dump_foreign($father);

        $father->setParam('name', 'father')->visualize(15);
        //die;

        //$mem('before');
        for ($i=0; $i<10; $i++) {
            //$sig = Indicator::make('Ema')->mutate(.5, 3)->getSignature();
            //$ind = $father->getOrAddIndicator($sig);
            //Log::debug('before');
            //$father->unsetIndicator($ind);
            //Log::debug('after');
            //$father->mutate();
            //$beagle->evaluate($father);
            //dump('Father '.$i.' fitness: '.$father->fitness());
            //$father->setParam('name', 'father')->visualize(15);
            //continue;

            $son = clone $father;
            $son->setParam('name', 'son '.$i);
            $son->mutate();
            $beagle->evaluate($son);
            dump('Son '.$i.' fitness: '.$son->fitness());
            $son->visualize(15);
            //$dump_foreign($son);
            //$son->kill();
        }

        /*
        $son = clone $father;
        $son->setParam('name', 'son');
        $son->mutate();
        dump('about to evaluate son');
        $beagle->evaluate($son);
        dump('Son fitness: '.$son->fitness());
        $son->visualize(15);
        */

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
