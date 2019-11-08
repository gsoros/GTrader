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
use GTrader\Indicator;

use GTrader\Evolution;
use GTrader\Evolvable;
use GTrader\Strategies\Tiktaalik;

use GTrader\Exceptions\MemoryLimitException;


class Beagle extends Training implements Evolution
{

    protected $generation = [];


    public function __construct(array $params = [])
    {
        $this->default_strategy = 'father';
        parent::__construct($params);

        $options = $this->options ?? [];
        foreach ([
            'population',
            'max_nesting',
            'mutation_rate',
            'loss_tolerance',
            'memory_limit',
        ] as $item) {
            if (!isset($options[$item])) {
                $options[$item] = $this->getParam($item);
            }
        }
        $this->options = $options;
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
        $this->father()->visualize(15);
        $generation = 0;
        $max_generations = 10000;

        while ($this->shouldRun() && ($generation < $max_generations)) {

            $generation++;

            $this->subscribeEvents()
                ->increaseEpoch()
                ->setProgress('father', $this->father()->fitness())
                ->saveHistory('father', $this->getProgress('best'), 'father')
                ->pruneHistory(0, 0, 0, 'father')
                ->killGeneration()
                ->raiseGeneration($this->options['population'])
                ->selection(1)
                ;

            if (!$champ = $this->generation()[0] ?? null) {
                $this->setProgress('last_error', 'generation is empty');
                dump('Generation '.$generation.' is empty');
                //\GTrader\Indicator::statCacheDump();
                continue;
            }
            $gen_best = $champ->fitness();
            dump('G: '.$generation.', Best: '.$gen_best.' EventSubs: '.Event::subscriptionCount());

            $this->setProgress('generation_best', $gen_best)
                ->saveHistory('generation_best', $gen_best, 'father')
                ->setProgress(
                    'no_improvement',
                    $this->getProgress('no_improvement') + 1
                );

            $champ->setCandles(clone $this->candles());
            $this->father()->kill();
            $this->father(clone $champ);

            if ($this->generationImproved()) {
                dump('New best: '.$this->getProgress('generation_best'));
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

        //Event::dumpSubscriptions();
        //\GTrader\DevUtil::memdump();
    }


    protected function init()
    {
        Indicator::decodeCacheEnabled(false);

        $options = $this->options;
        $options['memory_limit_bytes'] = ($options['memory_limit'] ?? 0) * 1048576;
        $this->options = $options;

        $reserve = intval($this->getParam('memory_reserve'));
        if ((0 <= $reserve) && (100 >= $reserve)) {
            $limit = intval(
                $options['memory_limit_bytes']
                + $options['memory_limit_bytes'] * $reserve / 100
            );
            ini_set('memory_limit', $limit);
            Log::debug(
                'Mem soft limit: '.\GTrader\Util::humanBytes($options['memory_limit_bytes']).
                ', hard limit: '.\GTrader\Util::humanBytes($limit)
            );
        }

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

        $this->candles($candles);

        $father = Strategy::load($this->strategy_id);
        $father->setParam(
            'mutation_rate',
            ($this->options['mutation_rate'] ?? $this->getParam['mutation_rate']) / 100
        );
        $father->setParam(
            'max_nesting',
            $this->options['max_nesting'] ?? $this->getParam['max_nesting']
        );
        $father->setCandles(clone $candles);
        $this->father($father);

        $father = clone $father;
        $this->evaluate($father);
        $this->setProgress('best', $father->fitness());
        //$this->father()->fitness($father->fitness());
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


    protected function subscribeEvents()
    {
        Event::subscribe('indicator.beforeCalculate', [$this, 'checkMemoryUsage']);
        return $this;
    }


    public function checkMemoryUsage()
    {
        if (!$limit = $this->options['memory_limit_bytes'] ?? 0) {
            return;
        }
        //$hb = function($b) { return \GTrader\Util::humanBytes($b); };
        //Log::debug($hb(memory_get_usage()), $hb($limit));
        if (memory_get_usage() >= $limit) {
            throw new MemoryLimitException();
        }
    }


    public function introduce(Evolvable $strategy): Evolution
    {
        $this->generation[] = $strategy;
        return $this;
    }


    public function raiseGeneration(int $size): Evolution
    {
        $gccc = gc_collect_cycles();
        //Log::debug('GC: '.$gccc);

        //$this->father()->getCandles()->purgeIndicators(['root', 'visible']);
        //$og_candles = clone $this->father()->getCandles();
        //$clone_candles = clone $og_candles;
        //$this->father()->setCandles($clone_candles);

        $candles = clone $this->candles();
        $father = clone $this->father();
        $father->setCandles($candles);

        for ($i = 0; $i < $size; $i++) {
            try {
                $offspring = clone $father;
                $offspring->mutate();
                $this->evaluate($offspring);
                if (($loss_tolerance = $this->lossTolerance())
                    && ($offspring->getMaxLoss() > $loss_tolerance)) {
                    dump('offspring #'.$i.' max loss: '.$offspring->getMaxLoss());
                    continue;
                }
                $this->introduce($offspring);
            } catch (MemoryLimitException $e) {
                dump('Mem limit reached at offspring #'.$i);
                break;
            }
        }

        $candles->kill();
        $father->kill();

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
        //$sig = $ind->getSignature();
        //dump('Beagle:evaluate('.$strategy->getParam('name').') ...'.substr($sig, strpos($sig, 'length'), 20).'...');
        $bal = $ind->calculated(false)->getLastValue();
        //$candles->unsetIndicator($ind);
        $strategy->fitness($bal);
        return $this;
    }


    protected function generationImproved(): bool
    {
        return $this->getProgress('generation_best') > $this->getProgress('best');
    }

    /*
    returns 0 if disabled
     */
    protected function lossTolerance(): int
    {
        if (($lt = intval($this->options['loss_tolerance'] ?? 0))
            && (0 <= $lt)
            && (100 > $lt)
        ) {
            return $lt;
        }
        return 0;
    }


    public function selection(int $num_survivors = 2): Evolution
    {
        if ($num_survivors < 1) {
            $this->iterateGeneration(function ($i, $s) {
                $this->killIndividual($i);
            });
            return $this;
        }

        $fitnesses = [];
        $this->iterateGeneration(function ($index, $strategy) use (&$fitnesses) {
            $fitnesses[$index] = $strategy->fitness();
        });
        arsort($fitnesses);

        $survivors = [];
        foreach ($fitnesses as $index => $fitness) {
            if (count($survivors) >= $num_survivors) {
                $this->killIndividual($index);
                continue;
            }
            $survivors[] = $this->generation()[$index];
        }
        $this->generation = $survivors;

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


    protected function candles(Series $set = null): Series
    {
        static $candles;

        if (!is_null($set)) {
            $candles = $set;
        }
        return $candles;
    }


    public function getPreferences()
    {
        $prefs = [];
        foreach ([
            'population',
            'max_nesting',
            'mutation_rate',
            'loss_tolerance',
            'memory_limit',
        ] as $item) {
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

        foreach ([
            'population',
            'max_nesting',
            'mutation_rate',
            'loss_tolerance',
            'memory_limit',
        ] as $item) {
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
