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
    use Skeleton;


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

    protected $lock;
    protected $strategies = [];
    protected $saved_fann;


    public function run()
    {
        $this->init();
        $this->obtainLock();
        $this->setProgress('state', 'training');
        $this->saveProgress();
        while ($this->shouldRun()) {
            $this->increaseEpoch();
            $this->swapIfCrossTraining();
            $this->train();
            $this->setProgress('train_mser', number_format($this->getStrategy('train')->getMSER(), 2, '.', ''));
            $this->saveHistory('train_mser', $this->getProgress('train_mser'));
            $this->copyFann('train', 'test');
            $test = $this->test('test');
            $this->setProgress('test', $test);
            $this->saveHistory('test', $test);
            $this->setProgress('no_improvement', $this->getProgress('no_improvement') + 1);
            if ($this->acceptable('test', 10)) {
                $this->copyFann('train', 'verify');
                $verify = $this->test('verify');
                $this->setProgress('verify', $verify);
                $this->saveHistory('verify', $verify);
                if ($this->acceptable('verify', 50)) {
                    $this->brake(50);
                }
                if ($this->acceptable('verify')) {
                    $this->setProgress('test_max', $test);
                    $this->setProgress('verify_max', $verify);
                    $this->setProgress('last_improvement_epoch', $this->getProgress('epoch'));
                    $this->setProgress('no_improvement', 0);
                    $this->setProgress('epoch_jump', 1);
                    $this->saveFann();
                }
            }
            $this->setProgress('signals', $this->getStrategy('test')->getNumSignals(true));
            $this->saveProgress();
            $this->increaseJump();
        }
        $this->setProgress('state', 'queued');
        $this->saveFann($this->getParam('suffix'));
        $this->releaseLock();
    }


    protected function brake(int $percent)
    {
        $jump = floor($this->getProgress('epoch_jump') * (100 - $percent) / 100);
        if ($jump < 2) {
            $jump = 1;
        }
        $this->setProgress('epoch_jump', $jump);
        return $this;
    }


    protected function getStrategy(string $type)
    {
        return isset($this->strategies[$type]) ? $this->strategies[$type] : null;
    }


    protected function setStrategy(string $type, FannStrategy $strategy)
    {
        $this->strategies[$type] = $strategy;
        return $this;
    }


    protected function train()
    {
        $this->getStrategy('train')->train($this->getProgress('epoch_jump'));
        return $this;
    }


    protected function copyFann(string $src, string $dest)
    {
        $this->getStrategy($dest)->setFann($this->getStrategy($src)->copyFann());
        return $this;
    }


    protected function saveFann(string $suffix = '')
    {
        $this->getStrategy('train')->saveFann($suffix);
    }


    protected function test(string $type)
    {
        return
            $this->getStrategy($type)
                ->getIndicatorLastValue(
                    $this->getParam('indicator'),
                    $this->getParam('indicator_params'),
                    true
                );
    }

    protected function increaseEpoch()
    {
        $this->setProgress(
            'epoch',
            $this->getProgress('epoch') + $this->getProgress('epoch_jump')
        );
        return $this;
    }


    protected function swapIfCrossTraining()
    {
        if (!$this->options['crosstrain']) {
            return $this;
        }

        $current_epoch = $this->getProgress('epoch');
        $last_epoch = max(
            $this->getProgress('last_improvement_epoch'),
            $this->getProgress('last_crosstrain_swap')
        );
        error_log('Epoch: '.$current_epoch.' Last: '.$last_epoch);

        if ($this->acceptable('test', 70) &&
            $current_epoch >= $last_epoch + $this->options['crosstrain']) {

            error_log('*** Swap ***');
            $this->setProgress('last_crosstrain_swap', $current_epoch);

            error_log('Before: '.$this->getProgress('test_before_swap').
                        ' Now: '.$this->getProgress('test'));
            if ($this->getProgress('test') < $this->getProgress('test_before_swap')) {
                error_log('Reverting fann');
                if (is_resource($this->saved_fann)) {
                    $this->getStrategy('train')->setFann($this->saved_fann);
                } else {
                    error_log('Saved fann not resource');
                }
            }

            // Swap train and test ranges
            $train_candles = $this->getStrategy('train')->getCandles();
            $test_candles = $this->getStrategy('test')->getCandles();
            $this->getStrategy('train')->setCandles($test_candles);
            $this->getStrategy('test')->setCandles($train_candles);

            $test = $this->test('test');

            // Set test baseline
            $this->setProgress('test_max', $test);

            // Save test for reverting
            $this->setProgress('test_before_swap', $test);

            // Save fann for reverting
            $this->saved_fann = $this->getStrategy('train')->copyFann();

        }
        return $this;
    }


    protected function init()
    {
        $exchange_name = Exchange::getNameById($this->exchange_id);
        $symbol_name = Exchange::getSymbolNameById($this->symbol_id);

        foreach ([
            'train_start', 'train_end',
            'test_start', 'test_end',
            'verify_start', 'verify_end'
        ] as $field) {
            if (!isset($this->options[$field]) || !$this->options[$field]) {
                throw new \Exception('Missing option: '.$field);
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

        define('FANN_WAKEUP_PREFERRED_SUFFX', $this->getParam('suffix'));
        $train_strategy = Strategy::load($this->strategy_id);
        $train_strategy->setCandles($train_candles);
        $this->setStrategy('train', $train_strategy);

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
        $this->setStrategy('test', $test_strategy);

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
        $this->setStrategy('verify', $verify_strategy);

        foreach ([
            'epochs' => 0,              // current epoch count
            'epoch_jump' => 1,          // number of epochs between value checks
            'no_improvement' => 0,      // keep track of the length of the period without improvement
            'test' => 0,                // test value
            'verify' => 0,              // verify value
            'signals' => 0,
            'state' => 'training',
        ] as $field => $default) {
            if (!isset($this->progress[$field])) {
                $this->setProgress($field, $default);
            }
        }

        $this->setProgress('test_max', $this->test('test'));
        $this->setProgress('verify_max', $this->test('verify'));
        $this->saveProgress();
    }


    protected function obtainLock()
    {
        $this->lock = 'training_'.$this->id;
        if (!Lock::obtain($this->lock)) {
            throw new \Exception('Could not obtain training lock for '.$this->id);
        }
        return $this;
    }


    protected function releaseLock()
    {
        Lock::release($this->lock);
        return $this;
    }


    protected function acceptable(string $type, int $allowed_regression_percent=0)
    {
        return
            $this->getProgress($type) >=
            $this->getProgress($type.'_max') *
            (100 - $allowed_regression_percent) / 100;
    }


    protected function saveHistory($name, $value)
    {
        error_log('saveHistory('.$this->getProgress('epoch').', '.$name.', '.$value.')');
        $this->getStrategy('train')
            ->saveHistory(
                $this->getProgress('epoch'),
                $name,
                $value
            );
        return $this;
    }


    protected function increaseJump()
    {
        if ($this->getProgress('no_improvement') > $this->getParam('max_boredom')) {
            // Increase jump size to fly over valleys faster, possibly missing some narrow peaks
            $this->setProgress('epoch_jump', $this->getProgress('epoch_jump') + 1);

            // Limit jumps
            if ($this->getProgress('epoch_jump') >= $this->getParam('epoch_jump_max')) {
                $this->setProgress('epoch_jump', $this->getParam('epoch_jump_max'));
            }
            $this->setProgress('no_improvement', 0);
        }
        return $this;
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


    protected function getProgress($key)
    {
        if (!is_array($this->progress)) {
            return 0;
        }
        return isset($this->progress[$key]) ? $this->progress[$key] : 0;
    }


    protected function saveProgress()
    {
        DB::table($this->table)
            ->where('id', $this->id)
            ->update(['progress' => json_encode($this->progress)]);
        return $this;
    }

}
