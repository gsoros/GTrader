<?php

namespace GTrader\Strategies;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use GTrader\Util;
use GTrader\Log;
use GTrader\Event;
use GTrader\Exchange;
use GTrader\Series;
use GTrader\Strategy;
use GTrader\Training;

use GTrader\Evolution;
use GTrader\Evolvable;
use GTrader\Strategies\Tiktaalik;

class Beagle extends Training implements Evolution
{

    protected $generation = [];


    public function __construct(array $params = [])
    {
        $this->default_strategy = 'father';
        parent::__construct($params);
    }


    public function __sleep()
    {
        $this->generation = [];
        return parent::__sleep();
    }


    public function __clone()
    {
        $this->generation = [];
        parent::__clone();
    }


    public function run()
    {
        //dump('Beagle::run()', $this->options);
        $this->init()
            ->obtainLock()
            ->setProgress('state', 'evolving')
            ->saveProgress();
        $og_candles = clone $this->father()->getCandles();
        $this->father()->visualize(15);
        $generation = 0;
        $max_generations = 10000;

        while ($this->shouldRun() && ($generation < $max_generations)) {

            $generation++;

            $this->increaseEpoch()
                ->setProgress('father', $this->father()->fitness())
                ->saveHistory('father', $this->getProgress('best'), 'father')
                ->pruneHistory(0, 0, 0, 'father')
                ->killGeneration()
                ->raiseGeneration($this->options['population'])
                ->selection(1)
                ;

            $gen_best = $this->generation()[0]->fitness();
            dump('G: '.$generation.', Best: '.$gen_best.' EventSubs: '.Event::subscriptionCount());

            $this->setProgress('generation_best', $gen_best)
                ->saveHistory('generation_best', $gen_best, 'father')
                ->setProgress(
                    'no_improvement',
                    $this->getProgress('no_improvement') + 1
                );

            $this->generation()[0]->setCandles(clone $og_candles);
            $this->father()->kill();
            $this->father(clone $this->generation()[0]);

            if ($gen_best > $this->getProgress('best')) {
                dump('New best: '.$gen_best);
                //$this->generation()[0]->setCandles(clone $og_candles)->save();
                //$this->father()->kill();
                //$this->father(clone $this->generation()[0]);
                $this->father()->save();
                $this->father()->visReset()->visualize();
                \GTrader\DevUtil::fdump(
                    $this->father()->visGetJSON(),
                    storage_path(
                        'dumps/'.
                        preg_replace( '/[^a-zA-Z0-9 ]+/', '-', $this->father()->getParam('name')).
                        '.json')
                );
                //die;
                $this->setProgress('best', $gen_best)
                    ->setProgress(
                        'last_improvement_epoch',
                        $this->getProgress('epoch')
                    )
                    ->setProgress('no_improvement', 0);
            }
            $this->logMemoryUsage()
                ->saveProgress();
            Event::clearSubscriptions();
            //Event::pruneSubscriptions();
        }

        $this->killGeneration()
            ->setProgress('state', 'queued')
            ->saveProgress()
            ->releaseLock();

        Event::dumpSubscriptions();
        //\GTrader\DevUtil::memdump();
    }


    protected function init()
    {
        $exchange_name = Exchange::getNameById($this->exchange_id);
        $symbol_name = Exchange::getSymbolNameById($this->symbol_id);

        foreach (['test_start', 'test_end'] as $field) {
            if (!isset($this->options[$field])) {
                throw new \Exception('Missing option: '.$field);
            }
        }

        $candles = new Series([
            'exchange' => $exchange_name,
            'symbol' => $symbol_name,
            'resolution' => $this->resolution,
            'start' => $this->options['test_start'],
            'end' => $this->options['test_end'],
            'limit' => 0
        ]);
        $candles->first(); // load
        Log::debug('Candles loaded', $candles->size());

        $father = Strategy::load($this->strategy_id);
        $father->setCandles($candles);
        $father->setParam('mutation_rate', $this->options['mutation_rate'] / 100 ?? 1);
        $father->setParam('max_nesting', $this->options['max_nesting'] ?? 3);
        $this->father($father);

        // workaround
        $this->evaluate($father = clone $father)->father()->fitness($father->fitness());
        //$this->debug('Start:     ');
        //$this->evaluate($father)->father()->fitness($father->fitness());
        //$this->debug('OG Father: ');
        /*
        $candles = clone $father->getCandles();
        for ($i=0; $i<10; $i++) {
            $son = clone $father;
            $son->mutate();
            $son->setCandles($candles);
            $this->evaluate($son);
            dump($candles->oid().' '.$son->fitness());
        }
        */
        //$this->debug('Clone 1:   ');
        //$this->evaluate((clone $father)->mutate())->father()->fitness($father->fitness());
        //$this->debug('Clone 2:   ');
        //$this->father()->getCandles()->purgeIndicators();
        //$this->debug('Purged:    ');
        //dd($this->father()->getCandles()->getMap());
        //die;

        $this->setProgress('epoch_jump', 1);
        if (!$this->getProgress('last_improvement_epoch')) {
            $this->setProgress('last_improvement_epoch', 0);
        }

        return $this;
    }


    public function introduce(Evolvable $strategy): Evolution
    {
        $this->generation[] = $strategy;
        return $this;
    }


    public function raiseGeneration(int $size): Evolution
    {
        //$this->father()->getCandles()->purgeIndicators(['root', 'visible']);
        $og_candles = clone $this->father()->getCandles();
        $clone_candles = clone $og_candles;
        $this->father()->setCandles($clone_candles);
        for ($i = 0; $i < $size; $i++) {
            $offspring = clone $this->father();
            $offspring->mutate();
            //$offspring->setCandles($candles);
            $this->evaluate($offspring);
            //$offspring->unsetCandles();
            $this->introduce($offspring);
        }
        $this->father()->setCandles($og_candles);

        Log::debug('I statcache: '.\GTrader\Indicator::statCacheSize());

        $clone_candles->kill();
        unset($clone_candles);
        //Log::debug('GC: '.gc_collect_cycles().' '.$og_candles->debug());

        return $this;
    }


    public function killIndividual($index): Evolution
    {
        if (!isset($this->generation[$index])) {
            Log::error($index.': no such index in generation');
            return $this;
        }
        $this->generation[$index]->kill();
        unset($this->generation[$index]);
        return $this;
    }


    public function killGeneration(): Evolution
    {
        $this->selection(0);
        return $this;
    }


    public function evaluate(Evolvable $strategy): Evolution
    {
        /*
        $strategy->setCandles($this->father()->getCandles());
        $sig = $this->getMaximizeSig($strategy); //dump(md5($sig));
        $ind = $strategy->getCandles()->getOrAddIndicator($sig);
        $bal = $ind->calculated(false)->getLastValue();
        */

        $sig = $this->getMaximizeSig($strategy); //dump($sig);
        $candles = $this->father()->getCandles();
        $ind = $candles->getOrAddIndicator($sig);
        $sig = $ind->getSignature();
        //dump('Beagle:evaluate('.$strategy->getParam('name').') ...'.substr($sig, strpos($sig, 'length'), 20).'...');
        $bal = $ind->calculated(false)->getLastValue();
        //$candles->unsetIndicator($ind);
        $strategy->fitness($bal);
        return $this;
    }



    public function selection(int $survivors = 2): Evolution
    {
        if ($survivors < 1) {
            $this->iterateGeneration(function ($i, $s) {
                $this->killIndividual($i);
            });
            return $this;
        }
        $a = [];
        $this->iterateGeneration(function ($i, $s) use (&$a) {
            $a[$i] = $s->fitness();
        });
        arsort($a);
        $new = [];
        $a = array_keys(array_slice($a, 0, $survivors, true));
        $this->iterateGeneration(function ($i, $s) use ($a, &$new) {
            if (in_array($i, $a)) {
                $new[] = $s;
            }
            else {
                $this->killIndividual($i);
            }
        });
        $this->generation = $new;
        return $this;
    }


    public function generation(): array
    {
        return $this->generation;
    }


    public function iterateGeneration(callable $callback): Beagle
    {
        Util::iterate($this->generation, $callback);
        return $this;
    }


    public function father($set = null)
    {
        if (null === $set) {
            return $this->getStrategy('father');
        }
        $this->setStrategy('father', $set);
        return $this;
    }


    public function getPreferences()
    {
        $prefs = [];
        foreach (['population', 'max_nesting', 'mutation_rate'] as $item) {
            $prefs[$item] = $this->getParam($item);
        }
        return array_replace_recursive(
            parent::getPreferences(),
            $prefs
        );
    }


    public function handleStartRequest(Request $request)
    {
        //dd($request->all());

        if (!$strategy = $this->loadStrategy()) {
            Log::error('Could not load strategy');
            return response('Strategy not found', 403);
        }

        $options = $this->options ?? [];
        $prefs = [];

        foreach (['population', 'max_nesting', 'mutation_rate'] as $item) {
            $prefs[$item] = $options[$item] = floatval($request->$item ?? 0);
        }

        $this->options = $options;

        Auth::user()->setPreference(
            $this->getShortClass(),
            $prefs
        )->save();

        if ($last_epoch = $strategy->getLastTrainingEpoch()) {
            Log::info('Continuing training from epoch '.$last_epoch);
            $this->progress = ['epoch' => $last_epoch];
        }

        $strategy->setParam(
            'last_training',
            array_merge($strategy->getParam('last_training', []), $options)
        )->save();

        return parent::handleStartRequest($request);
    }
}
