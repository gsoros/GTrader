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
use GTrader\Strategies\Fann as FannStrategy;

abstract class Training extends Model
{
    use Skeleton, HasCache;


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


    protected $lock;


    abstract public function run();


    public function __construct(array $params = [])
    {
        $this->skeletonConstruct($params);
        parent::__construct($params);
    }


    public function toHtml($content = null)
    {
        $prefs = $this->getPreferences();
        if ($class = $this->loadStrategy()->getParam('training_class')) {
            $prefs = Auth::user()->getPreference($class, $prefs);
        }
        if (!$strategy = Strategy::load($this->strategy_id)) {
            Log::error('Could not load strategy');
            return null;
        }
        return view('TrainingForm', [
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
        if (!($exchange_id = Exchange::getIdByName($exchange))) {
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

        $training = static::where('strategy_id', $this->getParam('strategy_id'))
            ->where('status', 'training')->first();
        if (is_object($training)) {
            Log::info('Strategy id('.$strategy->getParam('id').') is already being trained.');
            $html = view('TrainingProgress', [
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

        $from_scratch = true;
        if ($strategy->hasBeenTrained()) {
            $from_scratch = intval($request->from_scratch ?? 0);
        }

        if ($from_scratch) {
            Log::info('Training from scratch.');
            $strategy->fromScratch();
        } else {
            $last_epoch = $strategy->getLastTrainingEpoch();
            Log::info('Continuing training from epoch '.$last_epoch);
            $this->progress = ['epoch' => $last_epoch];
        }

        $this->save();

        return response(
            view('TrainingProgress', [
                'strategy' => $strategy,
                'training' => $this
            ]),
            200
        );
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


    protected function loadStrategy()
    {
        if (!$strategy_id = $this->getParam('strategy_id')) {
            Log::error('Strategy id not set');
            return null;
        }
        if (!$strategy = Strategy::load($strategy_id)) {
            Log::error('Could not load strategy', $strategy_id);
            return null;
        }
        return $strategy;
    }
}
