<?php

namespace GTrader;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

use GTrader\Exchange;
use GTrader\Skeleton;
use GTrader\HasCache;

abstract class Training extends Model
{
    use Skeleton, HasCache;

    protected $strategies = [];
    protected $default_strategy = 'default';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'trainings';

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


    abstract public function run();


    public function __construct(array $params = [])
    {
        $this->skeletonConstruct($params);
        parent::__construct($params);
    }


    public function isValid(): bool
    {
        if (!$strategy = $this->loadStrategy()) {
            return false;
        }
        return ($this->getShortClass() == $strategy->getParam('training_class'));
    }


    public function toHtml($content = null)
    {
        $prefs = $this->getPreferences();
        if (!$strategy = $this->loadStrategy()) {
            Log::error('Could not load strategy');
            return null;
        }
        if ($class = $strategy->getParam('training_class')) {
            $prefs = Auth::user()->getPreference($class, $prefs);
        }
        return view('Strategies.TrainingForm', [
            'training' => $this,
            'strategy' => $strategy,
            'preferences' => $prefs,
        ]);
    }


    public function getPreferences()
    {
        $prefs = [];
        foreach (array_keys($this->getParam('ranges'), []) as $item) {
            $prefs[$item.'_start_percent'] =
                $this->getParam('ranges.'.$item.'.start_percent');
            $prefs[$item.'_end_percent'] =
                $this->getParam('ranges.'.$item.'.end_percent');
        }
        $prefs['maximize_for'] = $this->getParam('maximize_for');
        return $prefs;
    }


    public function handleStartRequest(Request $request)
    {
        if (!$strategy = $this->loadStrategy()) {
            Log::error('Could not load strategy');
            return response('Strategy not found', 403);
        }

        $exchange = $request->exchange;
        if (!($exchange_id = Exchange::getOrAddIdByName($exchange))) {
            Log::error('Exchange not found ');
            return response('Exchange not found.', 403);
        }
        $symbol = $request->symbol;
        if (!($symbol_id = Exchange::getSymbolIdByExchangeSymbolName($exchange, $symbol))) {
            Log::error('Symbol not found ');
            return response('Symbol not found.', 403);
        }
        if (!($resolution = $request->resolution)) {
            Log::error('Resolution not found ');
            return response('Resolution not found.', 403);
        }

        $training = static::where('strategy_id', $this->strategy_id)
            ->where('status', 'training')->first();
        if (is_object($training)) {
            Log::info('Strategy id('.$strategy->getParam('id').') is already being trained.');
            $html = view('Strategies.TrainingProgress', [
                'strategy' => $strategy,
                'training' => $training
            ]);
            return response($html, 200);
        }

        $prefs = [];
        foreach (array_keys($this->getParam('ranges')) as $item) {
            ${$item.'_start_percent'} = doubleval($request->{$item.'_start_percent'});
            ${$item.'_end_percent'} = doubleval($request->{$item.'_end_percent'});
            if ((${$item.'_start_percent'} >= ${$item.'_end_percent'}) || !${$item.'_end_percent'}) {
                Log::error('Start or end not found for '.$item);
                return response('Input error.', 403);
            }
            $prefs[$item.'_start_percent'] = ${$item.'_start_percent'};
            $prefs[$item.'_end_percent'] = ${$item.'_end_percent'};
        }
        foreach (['maximize_for'] as $item) {
            if (isset($request->$item)) {
                $prefs[$item] = $request->$item;
            }
        }
        Auth::user()->setPreference(
            $strategy->getParam('training_class'),
            $prefs
        )->save();

        $candles = new Series([
            'exchange' => $exchange,
            'symbol' => $symbol,
            'resolution' => $resolution,
            'limit' => 0
        ]);
        $epoch = $candles->getEpoch();
        $last = $candles->getLastInSeries();
        $total = $last - $epoch;
        $options = $this->options ?? [];
        foreach (array_keys($this->getParam('ranges')) as $item) {
            $options[$item.'_start'] = floor($epoch + $total / 100 * ${$item.'_start_percent'});
            $options[$item.'_end']   = ceil($epoch + $total / 100 * ${$item.'_end_percent'});
        }

        $maximize = $this->getParam('maximize');
        $options['maximize_for'] = array_keys($maximize)[0];
        if (isset($request->maximize_for)) {
            if (array_key_exists($request->maximize_for, $maximize)) {
                $options['maximize_for'] = $request->maximize_for;
            }
        }

        $strategy->setParam(
            'last_training',
            array_merge([
                'exchange' => $exchange,
                'symbol' => $symbol,
                'resolution' => $resolution,
            ], $options)
        )->save();

        $this->status = 'training';
        $this->exchange_id = $exchange_id;
        $this->symbol_id = $symbol_id;
        $this->resolution = $resolution;
        $this->options = $options;
        $this->progress = [];

        $this->save();

        return response(
            view('Strategies.TrainingProgress', [
                'strategy' => $strategy,
                'training' => $this
            ]),
            200
        );
    }


    public function getStrategy($type = null)
    {
        $type = ($type = strval($type)) ?? $this->default_strategy;
        return $this->strategies[$type] ?? null;
    }


    public function setStrategy($type = null, Strategy $strategy)
    {
        $type = ($type = strval($type)) ?? $this->default_strategy;
        $this->strategies[$type] = $strategy;
        return $this;
    }


    public function loadStrategy()
    {
        if (!$strategy_id = $this->strategy_id) {
            Log::error('Strategy id not set');
            return null;
        }
        if (!$strategy = Strategy::load($strategy_id)) {
            Log::error('Could not load strategy', $strategy_id);
            return null;
        }
        return $strategy;
    }


    public function getMaximizeSig(Strategy $strategy)
    {
        //dump('maxi sig for '.$strategy->oid());
        if ($sig = $strategy->cached('maximize_sig')) {
            //Log::debug('cached max sig');
            return $sig;
        }
        $maximize = $this->options['maximize_for'] ??
            array_keys($this->getParam('maximize'))[0];

        switch ($maximize) {
            case 'balance_fixed':
                $indicator = $strategy->getBalanceIndicator();
                break;

            case 'balance_dynamic':
                $indicator = $strategy->getBalanceIndicator();
                $indicator->setParam('indicator.mode', 'dynamic');
                break;

            case 'profitability':
                $signals = $strategy->getSignalsIndicator();
                $indicator = $signals->getOwner()->getOrAddIndicator('Profitability', [
                    'input_signal' => $signals->getSignature(),
                ]);
                break;

            case 'avg_balance':
                $bal = $strategy->getBalanceIndicator();
                $indicator = $bal->getOwner()->getOrAddIndicator('Avg', [
                    'input_source' => $bal->getSignature(),
                ]);
                break;

            default:
                Log::error('Unknown maximize target');
                return null;
        }
        $sig = $indicator->getSignature();
        $strategy->cache('maximize_sig', $sig);
        //Log::debug('...'.substr($sig, strpos($sig, 'length'), 20).'...');
        return $sig;
    }


    protected function setProgress($key, $value)
    {
        if (is_numeric($value)) {
            if (intval($value) != $value) {
                $value = number_format($value, 2, '.', '');
            }
        }
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
        return $this->progress[$key] ?? 0;
    }


    protected function saveProgress()
    {
        DB::table($this->table)
            ->where('id', $this->id)
            ->update(['progress' => json_encode($this->progress)]);
        return $this;
    }



    protected function saveHistory(
        string $name,
        $value,
        string $strat_name = 'default')
    {
        dump('E: '.$this->getProgress('epoch').', '.$name.': '.$value);
        $this->getStrategy($strat_name)
            ->saveHistory(
                $this->getProgress('epoch'),
                $name,
                $value
            );
        return $this;
    }


    protected function pruneHistory(
        int $limit = 0,
        int $epochs = 0,
        int $nth = 0,
        string $strat_name = 'default')
    {

        if (!$limit)  $limit = 15000;
        if (!$epochs) $epochs = 1000;
        if (!$nth)    $nth = 2;

        $current_epoch = $this->getProgress('epoch');
        if ($current_epoch <= $this->getProgress('last_history_prune') + $epochs) {
            return $this;
        }
        if ($this->getStrategy($strat_name)->getHistoryNumRecords() > $limit) {
            dump('Pruning history');
            $state = $this->getProgress('state');
            $this->setProgress('last_history_prune', $current_epoch)
                ->setProgress('state', 'pruning history')
                ->saveProgress();
            $this->getStrategy($strat_name)->pruneHistory($nth);
            $this->setProgress('state', $state)->saveProgress();
        }
        return $this;
    }


    protected function logMemoryUsage()
    {
        dump('Memory used: '.Util::getMemoryUsage());
        return $this;
    }


    protected function shouldRun()
    {
        if (!$this->started) {
            $this->started = time();
            dump('Training start: '.date('Y-m-d H:i:s'));
        }

        // check db if we have been stopped or deleted
        try {
            self::where('id', $this->id)
                ->where('status', 'training')
                ->firstOrFail();
        } catch (\Exception $e) {
            dump('Training stopped.');
            return false;
        }
        // check if the number of active trainings is greater than the number of slots
        if (self::where('status', 'training')->count() > TrainingManager::getSlotCount()) {
            // check if we have spent too much time
            if ((time() - $this->started) > $this->getParam('max_time_per_session')) {
                dump('Time up: '.(time() - $this->started).'/'.$this->getParam('max_time_per_session'));
                return false;
            }
        }
        return true;
    }

    protected function increaseEpoch()
    {
        $this->setProgress(
            'epoch',
            $this->getProgress('epoch') + 1
        );
        return $this;
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
}
