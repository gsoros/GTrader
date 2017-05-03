<?php

namespace GTrader;

//use GTrader\Strategies\Fann as FannStrategy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use GTrader\Lock;
use GTrader\Exchange;
use GTrader\Series;
use GTrader\Strategy;
use GTrader\Strategies\Fann as FannStrategy;
use GTrader\TrainingManager;

class FannTraining extends Model
{
    use Skeleton, HasStrategy;


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fann_training';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'options' => 'array',
        'progress' => 'array',
        'history' => 'array',
    ];


    public function run()
    {
        echo 'FannTraining::run() ID:'.$this->id."\n";

        $training_lock = 'training_'.$this->id;
        if (!Lock::obtain($training_lock)) {
            throw new \Exception('Could not obtain training lock for '.$this->id);
        }

        $exchange_name = Exchange::getNameById($this->exchange_id);
        $symbol_name = Exchange::getSymbolNameById($this->symbol_id);

        $train_suffix = '.train';

        foreach ([
            'train_start', 'train_end',
            'test_start', 'test_end',
            'verify_start', 'verify_end'
        ] as $field) {
            if (!isset($this->options[$field]) || !$this->options[$field]) {
                throw new \Exception('Missing field: '.$field);
            }
        }

        // Set up training strategy
        $train_candles = new Series([
            'exchange' => $exchange_name,
            'symbol' => $symbol_name,
            'resolution' => $this->resolution,
            'start' => $this->options['train_start'],
            'end' => $this->options['train_end'],
            'limit' => 0
        ]);

        define('FANN_WAKEUP_PREFERRED_SUFFX', $train_suffix);
        $train_strategy = Strategy::load($this->strategy_id);
        $train_strategy->setCandles($train_candles);

        // Set up test strategy
        $test_candles = new Series([
            'exchange' => $exchange_name,
            'symbol' => $symbol_name,
            'resolution' => $this->resolution,
            'start' => $this->options['test_start'],
            'end' => $this->options['test_end'],
            'limit' => 0
        ]);
        $test_strategy = clone $train_strategy;
        $test_strategy->setCandles($test_candles);

        // Set up verify strategy
        $verify_candles = new Series([
            'exchange' => $exchange_name,
            'symbol' => $symbol_name,
            'resolution' => $this->resolution,
            'start' => $this->options['verify_start'],
            'end' => $this->options['verify_end'],
            'limit' => 0
        ]);
        $verify_strategy = clone $train_strategy;
        $verify_strategy->setCandles($verify_candles);

        foreach ([
            'epochs' => 0,              // current epoch count
            'epoch_jump' => 1,          // number of epochs between value checks
            'no_improvement' => 0,      // keep track of the length of the period without improvement
            'test' => 0,                // test value
            'test_max' => 0,            // max test value
            'verify' => 0,              // verify value
            'verify_max' => 0,          // max verify value
            'signals' => 0,
            'state' => 'training',
        ] as $field => $default) {
            if (!isset($this->progress[$field])) {
                $this->setProgress($field, $default);
            }
        }

        $this->saveProgress();

        $max_boredom = 10;      // increase jump size after this many checks without improvement
        $epoch_jump_max = 100;  // max amount of skipped epochs
        $test_regression = .9;  // allow this amount of regression to test max

        //$indicator = 'Balance';
        //$indicator = 'Profitability';
        //$indicator_params = [];
        $indicator = 'Avg';
        $indicator_params = ['indicator' => ['base' => 'Balance_mode_fixed_capital_100']];


        if (!$this->progress['test_max']) {
            $this->setProgress(
                'test_max',
                $test_strategy->getIndicatorLastValue($indicator, $indicator_params, true)
            );
        }

        if (!$this->progress['verify_max']) {
            $this->setProgress(
                'verify_max',
                $verify_strategy->getIndicatorLastValue($indicator, $indicator_params, true)
            );
        }

        $prev_test = 0;

        while ($this->shouldRun()) {

            $this->setProgress('state', 'training');

            $this->setProgress(
                'epochs',
                $this->progress['epochs'] + $this->progress['epoch_jump']
            );

            // Do the actual training
            $train_strategy->train($this->progress['epoch_jump']);

            // Assign fann to test strat
            $test_strategy->setFann($train_strategy->copyFann());

            // Get test value
            $this->setProgress(
                'test',
                $test_strategy->getIndicatorLastValue($indicator, $indicator_params, true)
            );

            // Save test value to history
            $train_strategy->saveHistory(
                $this->progress['epochs'],
                'test',
                $this->progress['test']
            );

            // Save test MSER to history
            $train_strategy->saveHistory(
                $this->progress['epochs'],
                'test_MSER',
                $train_strategy->getMSER()
            );

            $this->setProgress(
                'no_improvement',
                $this->progress['no_improvement'] + 1
            );

            if ($this->progress['test'] > $this->progress['test_max'] * $test_regression) {

                // There is improvement
                $this->setProgress('no_improvement', 0);

                /*
                if ($this->progress['test'] > $this->progress['test_max']) {
                    $this->setProgress('test_max', $this->progress['test']);
                }
                */

                // Assign fann to verify strat
                $verify_strategy->setFann($train_strategy->copyFann());

                // Get verify value
                $this->setProgress(
                    'verify',
                    $verify_strategy->getIndicatorLastValue($indicator, $indicator_params, true)
                );

                // Save verify value to history
                $train_strategy->saveHistory(
                    $this->progress['epochs'],
                    'verify',
                    $this->progress['verify']
                );

                if ($this->progress['verify'] > $this->progress['verify_max']) {

                    $this->setProgress('verify_max',$this->progress['verify']);

                    // Update test max
                    if ($this->progress['test'] > $this->progress['test_max']) {
                        $this->setProgress('test_max', $this->progress['test']);
                    }

                    // Save the fann
                    $train_strategy->saveFann();
                    $this->setProgress('epoch_jump', 1);
                }
            }

            if ($this->progress['no_improvement'] > $max_boredom) {
                // Increase jump size to fly over valleys faster, possibly missing some narrow peaks
                $this->setProgress('epoch_jump', $this->progress['epoch_jump'] + 1);

                // Limit jumps
                if ($this->progress['epoch_jump'] >= $epoch_jump_max) {
                    $this->setProgress('epoch_jump', $epoch_jump_max);
                }
                $this->setProgress('no_improvement', 0);
            }

            $this->setProgress('signals', $test_strategy->getNumSignals(true));
            $this->saveProgress();
        }

        // Put training back to queue
        $this->setProgress('state', 'queued');
        $this->saveProgress();

        // Save current trained fann for the next run
        $train_strategy->saveFann($train_suffix);

        Lock::release($training_lock);
    }



    protected function shouldRun()
    {
        static $started;

        if (!$started) {
            $started = time();
        }

        // check db if we have been stopped or deleted
        try {
            self::where('id', $this->id)
                ->where('status', 'training')
                ->firstOrFail();
        } catch (\Exception $e) {
            error_log('Training stopped.');
            return false;
        }
        // check if the number of active trainings is greater than the number of slots
        if (self::where('status', 'training')->count() > TrainingManager::getSlotCount()) {
            // check if we have spent too much time
            if ((time() - $started) > $this->getParam('max_time_per_session')) {
                error_log('Time up: '.(time() - $started).'/'.$this->getParam('max_time_per_session'));
                return false;
            }
        }
        return true;
    }


    protected function setProgress($key, $value)
    {
        $progress = $this->progress;
        if (!is_array($progress)) {
            $progress = [];
        }
        $this->progress = array_replace_recursive($progress, [$key => $value]);
        return $this;
    }

    protected function saveProgress()
    {
        DB::table($this->table)
            ->where('id', $this->id)
            ->update(['progress' => json_encode($this->progress)]);
        return $this;
    }

}
