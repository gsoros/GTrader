<?php

namespace GTrader\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GTrader\Strategy;
use GTrader\Series;
use GTrader\Indicator;
use GTrader\Util;
use GTrader\Chart;
use GTrader\Exchange;
use GTrader\FannTraining;
use GTrader\Plot;

if (!extension_loaded('fann')) {
    throw new \Exception('FANN extension not loaded');
}

class Fann extends Strategy
{

    protected $_fann = null;                // fann resource
    protected $_data = [];
    protected $_sample_iterator = 0;
    protected $_callback_type = false;
    protected $_callback_iterator = 0;
    protected $_bias = null;


    public function __construct(array $params = [])
    {
        //error_log('Fann::__construct()');
        parent::__construct($params);
        $this->setParam('num_output', 1);
    }


    public function __wakeup()
    {
        parent::__wakeup();
        if (defined('FANN_WAKEUP_PREFERRED_SUFFX')) {
            //error_log('Fann::__wakeup() Hacked path: '.$this->path().FANN_WAKEUP_PREFERRED_SUFFX);
            $this->loadOrCreateFann(FANN_WAKEUP_PREFERRED_SUFFX);
        } else {
            //error_log('Fann::__wakeup() path: '.$this->path());
            $this->loadOrCreateFann();
        }
    }


    public function toHTML(string $content = null)
    {
        return parent::toHTML(
            view('Strategies/'.$this->getShortClass().'Form', ['strategy' => $this])
        );
    }


    public function getTrainingChart()
    {
        $exchange = Exchange::getDefault('exchange');
        $symbol = Exchange::getDefault('symbol');
        $resolution = Exchange::getDefault('resolution');
        $mainchart = session('mainchart');
        if (is_object($mainchart)) {
            $exchange = $mainchart->getCandles()->getParam('exchange');
            $symbol = $mainchart->getCandles()->getParam('symbol');
            $resolution = $mainchart->getCandles()->getParam('resolution');
        }
        $candles = new Series([
            'limit' => 0,
            'exchange' => $exchange,
            'symbol' => $symbol,
            'resolution' => $resolution,
        ]);
        $training_chart = Chart::make(null, [
            'candles' => $candles,
            'name' => 'trainingChart',
            'height' => 200,
            'disabled' => ['title', 'map', 'panZoom', 'strategy', 'settings'],
        ]);
        $training_chart->saveToSession();
        return $training_chart;
    }


    public function getTrainingProgressChart(FannTraining $training)
    {
        $candles = new Series([
            'limit' => 0,
            'exchange' => Exchange::getNameById($training->exchange_id),
            'symbol' => Exchange::getSymbolNameById($training->symbol_id),
            'resolution' => $training->resolution,
        ]);

        $highlights = [];
        foreach (['train', 'test', 'verify'] as $range) {
            if (isset($training->options[$range.'_start']) && isset($training->options[$range.'_end'])) {
                $highlights[] = [
                    'start' => $training->options[$range.'_start'],
                    'end' => $training->options[$range.'_end']
                ];
            }
        }

        $progress_chart = Chart::make(null, [
            'candles' => $candles,
            'strategy' => $this,
            'name' => 'trainingProgressChart',
            'height' => 200,
            'disabled' => ['title', 'strategy', 'map', 'settings'],
            'readonly' => ['esr'],
            'highlight' => $highlights,
            'visible_indicators' => ['Balance', 'Profitability'],
        ]);

        $sig = $training->getMaximizeSig();
        if (!$progress_chart->hasIndicator($sig)) {
            $progress_chart->addIndicatorBySignature($sig);
            $this->save();
        }

        if (!$progress_chart->hasIndicatorClass('Balance')) {
            $progress_chart->addIndicator('Balance');
            $this->save();
        }

        if (!$progress_chart->hasIndicatorClass('Profitability')) {
            $progress_chart->addIndicator('Profitability');
            $this->save();
        }

        $progress_chart->saveToSession();

        return $progress_chart;
    }


    public function getHistoryPlot(int $width, int $height)
    {
        $data = [];
        $items = DB::table('fann_history')
            ->select('epoch', 'name', 'value')
            ->where('strategy_id', $this->getParam('id'))
            ->orderBy('epoch', 'desc')
            ->orderBy('name', 'desc')
            ->limit(15000)
            ->get()
            ->reverse()
            ->values();
        foreach ($items as $item) {
            if (!array_key_exists($item->name, $data)) {
                $data[$item->name] = [];
            }
            $data[$item->name][$item->epoch] = $item->value;
        }
        ksort($data);
        $plot = new Plot([
            'name' => 'History',
            'width' => $width,
            'height' => $height,
            'data' => $data
        ]);
        return $plot->toHTML();
    }


    public function handleSaveRequest(Request $request)
    {
        $topology_changed = false;

        $inputs = isset($request->inputs) ? $request->inputs : ['open'];
        $inputs = is_array($inputs) ? $inputs : ['open'];
        $inputs = count($inputs) ? $inputs : ['open'];
        if ($this->getParam('inputs', []) !== $inputs) {
            $topology_changed = true;
            $this->setParam('inputs', $inputs);
            error_log('New inputs: '.json_encode($inputs));
        }


        if (isset($request->hidden_array)) {
            $hidden_array = explode(',', $request->hidden_array);
            if (count($hidden_array)) {
                $request->hidden_array = [];
                foreach ($hidden_array as $hidden_layer) {
                    if (($hidden_layer = intval($hidden_layer)) && $hidden_layer > 0) {
                        $request->hidden_array[] = $hidden_layer;
                    }
                }
            }
            $current_hidden_array = $this->getParam('hidden_array');
            if (count($request->hidden_array) &&
                $current_hidden_array !== $request->hidden_array) {
                $topology_changed = true;
                $this->setParam('hidden_array', $request->hidden_array);
            }
        }

        $sample_size = $this->getParam('sample_size');
        if (isset($request->sample_size)) {
            $sample_size = intval($request->sample_size);
            if ($sample_size < 2) {
                $sample_size = 2;
            }
            if ($sample_size !== intval($this->getParam('sample_size'))) {
                $topology_changed = true;
            }
            $this->setSampleSize($sample_size);
        }

        if ($topology_changed) {
            error_log('Strategy '.$this->getParam('id').': topology changed, deleting fann.');
            $this->destroyFann();
            $this->deleteFiles();
        }

        foreach (['target_distance', 'long_threshold', 'short_threshold'] as $param) {
            if (isset($request->$param)) {
                $this->setParam($param, intval($request->$param));
            }
        }

        parent::handleSaveRequest($request);
        return $this;
    }


    public function listItem()
    {
        $training = FannTraining::select('status')
            ->where('strategy_id', $this->getParam('id'))
            ->where(function ($query) {
                $query->where('status', 'training')
                        ->orWhere('status', 'paused');
            })
            ->first();
        $training_status = null;
        if (is_object($training)) {
            $training_status = $training->status;
        }

        return view(
            'Strategies/FannListItem',
            [
                'strategy' => $this,
                'training_status' => $training_status
            ]
        );
    }


    public function getPredictionIndicator()
    {
        $class = $this->getParam('prediction_indicator_class');

        $candles = $this->getCandles();

        $indicator = null;
        foreach ($candles->getIndicators() as $candidate) {
            if ($class === $candidate->getShortClass()) {
                $indicator = $candidate;
            }
        }
        if (is_null($indicator)) {
            $indicator = Indicator::make($class, ['display' => ['visible' => false]]);
            $candles->addIndicator($indicator);
        }

        $ema_len = $this->getParam('prediction_ema');
        if ($ema_len > 1) {
            $indicator = Indicator::make(
                'Ema',
                ['indicator' => ['base' => $indicator->getSignature(), 'length' => $ema_len],
                 'display' => ['visible' => false],
                 'depends' => [$indicator]]
            );

            $candles->addIndicator($indicator);
            $indicator = $candles->getIndicator($indicator->getSignature());
        }

        return $indicator;
    }


    public function loadOrCreateFann(string $prefer_suffix = '')
    {
        if (is_resource($this->_fann)) {
            throw new \Exception('loadOrCreateFann called but _fann is already a resource');
        }

        // try first with suffix, if supplied
        if (strlen($prefer_suffix)) {
            $this->loadFann($this->path().$prefer_suffix);
        }

        // try without suffix
        if (!is_resource($this->_fann)) {
            $this->loadFann($this->path());
        }

        // create a new fann
        if (!is_resource($this->_fann)) {
            $this->createFann();
        }
        return true;
    }


    public function loadFann($path)
    {
        if (is_file($path) && is_readable($path)) {
            //error_log('creating fann from '.$path);
            $this->_fann = fann_create_from_file($path);
            return true;
        }
        return false;
    }


    public function createFann()
    {
        //error_log('Fann::createFann() Input: '.$this->getNumInput());
        if ($this->getParam('fann_type') === 'fixed') {
            $params = array_merge(
                [$this->getNumLayers()],
                [$this->getNumInput()],
                $this->getParam('hidden_array'),
                [$this->getParam('num_output')]
            );
            //error_log('calling fann_create_shortcut('.join(', ', $params).')');
            //$this->_fann = call_user_func_array('fann_create_standard', $params);
            $this->_fann = call_user_func_array('fann_create_shortcut', $params);
        } elseif ($this->getParam('fann_type') === 'cascade') {
            $this->_fann = fann_create_shortcut(
                $this->getNumLayers(),
                $this->getNumInput(),
                $this->getParam('num_output')
            );
        } else {
            throw new \Exception('Unknown fann type');
        }
        $this->initFann();
        $this->reset();
        return true;
    }


    public function reset()
    {
        fann_randomize_weights($this->_fann, -0.77, 0.77);
        return true;
    }


    public function initFann()
    {
        if (!is_resource($this->_fann)) {
            throw new \Exception('Cannot init fann, not a resource');
        }
        fann_set_activation_function_hidden($this->_fann, FANN_SIGMOID_SYMMETRIC);
        //fann_set_activation_function_output($this->_fann, FANN_SIGMOID_SYMMETRIC);
        //fann_set_activation_function_hidden($this->_fann, FANN_GAUSSIAN_SYMMETRIC);
        fann_set_activation_function_output($this->_fann, FANN_GAUSSIAN_SYMMETRIC);
        //fann_set_activation_function_hidden($this->_fann, FANN_LINEAR);
        //fann_set_activation_function_output($this->_fann, FANN_LINEAR);
        //fann_set_activation_function_hidden($this->_fann, FANN_ELLIOT_SYMMETRIC);
        //fann_set_activation_function_output($this->_fann, FANN_ELLIOT_SYMMETRIC);
        if ($this->getParam('fann_type') === 'fixed') {
            //fann_set_training_algorithm($this->_fann, FANN_TRAIN_INCREMENTAL);
            //fann_set_training_algorithm($this->_fann, FANN_TRAIN_BATCH);
            fann_set_training_algorithm($this->_fann, FANN_TRAIN_RPROP);
            //fann_set_training_algorithm($this->_fann, FANN_TRAIN_QUICKPROP);
            //fann_set_training_algorithm($this->_fann, FANN_TRAIN_SARPROP);
        }
        //fann_set_train_error_function($this->_fann, FANN_ERRORFUNC_LINEAR);
        fann_set_train_error_function($this->_fann, FANN_ERRORFUNC_TANH);
        //fann_set_learning_rate($this->_fann, 0.2);
        $this->_bias = null;
        return true;
    }


    public function getFann()
    {
        if (!is_resource($this->_fann)) {
            $this->loadOrCreateFann();
        }
        return $this->_fann;
    }


    public function copyFann()
    {
        return fann_copy($this->getFann());
    }


    public function setFann($fann)
    {
        if (!is_resource($fann)) {
            throw new \Exception('supplied fann is not a resource');
        }
        //error_log('setFann('.get_resource_type($fann).')');
        //var_dump(debug_backtrace());
        //if (is_resource($this->_fann)) $this->destroyFann(); // do not destroy, it may have a reference
        $this->_fann = $fann;
        $this->initFann();
        return true;
    }


    public function saveFann(string $suffix = '')
    {
        $fn = $this->path().$suffix;
        if (!fann_save($this->getFann(), $fn)) {
            error_log('saveFann to '.$fn.' failed');
            return false;
        }
        if (!chmod($fn, 0666)) {
            error_log('chmod of '.$fn.' failed');
            return false;
        }
        return true;
    }



    public function delete()
    {
        // remove trainings
        FannTraining::where('strategy_id', $this->getParam('id'))->delete();
        // remove files
        $this->deleteFiles();
        // remove training history
        $this->deleteHistory();
        // remove strategy
        return parent::delete();
    }


    public function deleteHistory()
    {
        $affected = DB::table('fann_history')
            ->where('strategy_id', $this->getParam('id'))
            ->delete();
        error_log('Fann::deleteHistory() '.$affected.' records deleted.');
        return $this;
    }


    public function saveHistory(int $epoch, string $name, float $value)
    {
        DB::table('fann_history')
            ->insert([
                'strategy_id' => $this->getParam('id'),
                'epoch' => $epoch,
                'name' => $name,
                'value' => $value,
            ]);
        return $this;
    }


    public function getHistoryNumRecords()
    {
        return DB::table('fann_history')
            ->where('strategy_id', $this->getParam('id'))
            ->count();
    }


    public function pruneHistory(int $nth = 2)
    {
        if ($nth < 2) {
            $nth = 2;
        }
        $epochs = DB::table('fann_history')
            ->select('epoch')
            ->distinct()
            ->where('strategy_id', $this->getParam('id'))
            ->get();
        $count = 1;
        $deleted = 0;
        foreach ($epochs as $epoch) {
            if ($count == $nth) {
                $deleted +=  DB::table('fann_history')
                    ->where('strategy_id', $this->getParam('id'))
                    ->where('epoch', $epoch->epoch)
                    ->delete();
            }
            $count ++;
            if ($count > $nth) {
                $count = 1;
            }
        }
        error_log($deleted.' history records deleted.');
        return $this;
    }


    public function getLastTrainingEpoch()
    {
        $res = DB::table('fann_history')
            ->select('epoch')
            ->where('strategy_id', $this->getParam('id'))
            ->orderBy('epoch', 'desc')
            ->limit(1)
            ->first();
        return is_object($res) ? intval($res->epoch) : 0;
    }


    public function deleteFiles()
    {
        $fann = $this->path();
        foreach ([
            $fann,
            $fann.'.train',
            storage_path('logs/'.$this->getParam('training_log_prefix').$this->getParam('id').'.log')
        ] as $file) {
            error_log('Checking to delete '.$file);
            if (is_file($file)) {
                if (!is_writable($file)) {
                    error_log($file.' not writable');
                    continue;
                }
                unlink($file);
            }
        }
        return $this;
    }


    public function destroyFann()
    {
        if (is_resource($this->_fann)) {
            return fann_destroy($this->_fann);
        }
        return true;
    }


    public function run($input, $ignore_bias = false)
    {
        try {
            $output = fann_run($this->getFann(), $input);
            if (!$ignore_bias) {
                $output[0] -= $this->getBias();
            }
            return $output[0];
        } catch (\Exception $e) {
            error_log('fann_run error: '.$e->getMessage()."\n".
                        ' Input: '.serialize($input));
            return null;
        }
    }


    public function getBias()
    {
        if (!$this->getParam('bias_compensation')) {
            return 0; // bias disabled
        }
        if (!is_null($this->_bias)) {
            return $this->_bias * $this->getParam('bias_compensation');
        }
        $this->_bias = fann_run($this->getFann(), array_fill(0, $this->getNumInput(), 0))[0];
        //error_log('bias: '.$this->_bias);
        return $this->_bias * $this->getParam('bias_compensation');
    }


    public function resetSample()
    {
        $this->_sample_iterator = 0;
        $this->nextSample(null, 'reset');
        return true;
    }


    public function nextSample($size = null, $reset = false)
    {
        static $___sample = [];

        if ($reset == 'reset') {
            $___sample = [];
            return true;
        }

        $candles = $this->getCandles();

        //error_log('nextSample() candles: '.$candles->debugObjId());
        //error_log(json_encode($candles->first()));
        //exit();

        if (!$candles->size()) {
            return null;
        }

        if (!$size) {
            $size = $this->getParam('sample_size') + $this->getParam('target_distance');
        }

        while ($candle = $candles->byKey($this->_sample_iterator)) {
            $this->_sample_iterator++;
            $___sample[] = $candle;
            $current_size = count($___sample);
            if ($current_size <  $size) {
                continue;
            }
            if ($current_size == $size) {
                //error_log('nextSample S: '.json_encode($___sample));
                return $___sample;
            }
            if ($current_size >  $size) {
                array_shift($___sample);
                return $___sample;
            }
        }
        return null;
    }


    public function runInputIndicators(bool $force_rerun = false)
    {
        $inputs = $this->getParam('inputs', []);
        $candles = $this->getCandles();
        foreach ($inputs as $sig) {
            if (!($indicator = $candles->getOrAddIndicator(
                $sig,
                ['display' => ['visible' => false]]
            ))) {
                //error_log('runInputIndicators() could not getOrAddIndicator() '.$sig);
                continue;
            }
            $indicator->checkAndRun($force_rerun);
        }
        return $this;
    }


    public function getInputGroups(bool $force_rerun = false)
    {
        static $groups = null;

        if (!is_null($groups) && !$force_rerun) {
            return $groups;
        }

        $inputs = $this->getParam('inputs', []);
        $groups = [];

        reset($inputs);
        foreach ($inputs as $sig) {
            $norm_type = $to_zero = null;
            if (in_array($sig, ['open', 'high', 'low', 'close'])) {
                //error_log('Fann::getInputGroups() '.$sig.' is ohlc');
                $norm_type = 'ohlc';
            }
            elseif ('volume' === $sig) {
                $norm_type = 'individual';
            }
            elseif ($indicator = $this->getCandles()->getOrAddIndicator($sig)) {
                if (!($norm_type = $indicator->getNormalizeType())) {
                    error_log('Fann::getInputGroups() could not getNormalizeType() for '.$sig);
                    continue;
                }
                if ('individual' === $norm_type) {
                    $to_zero = $indicator->getParam('normalize_to_zero');
                }
            }
            else {
                error_log('Fann::getInputGroups() could not getOrAddIndicator() '.$sig);
                continue;
            }
            if ('ohlc' === $norm_type) {
                $groups['ohlc'][$sig] = true;
                continue;
            }
            if ('range' === $norm_type) {
                if (is_null($min = $indicator->getParam('range.min', null)) ||
                    is_null($max = $indicator->getParam('range.max', null))) {
                    error_log('Fann::getInputGroups() min or max range not set for '.$sig);
                    continue;
                }
                $groups['range'][$sig] = ['min' => $min, 'max' => $max];
                continue;
            }
            if ('individual' === $norm_type) {
                $groups['individual'][$sig] = $to_zero ? ['to_zero' => true] : true;
                continue;
            }
            error_log('Fann::getInputGroups() unknown normalize type for '.$sig);
        }
        //error_log('getInputGroups() groups: '.json_encode($groups));
        return $groups;
    }


    public function sample2io(array $sample, bool $input_only = false) {
        /*
        input: [
            'ohlc' => [
                0 => [
                    'values' => [1, 2, 3, ...]
                ]
            ],
            'range' => [
                'Rsi_base_close_length_14' => [
                    'min' => -100,
                    'max' => 100,
                    'values' => [1, 2, 3, ...]
                ]
            ],
            'individual' => [
                'Ema_base_volume_length_20' => [
                    'values' => [1, 2, 3, ...]
                ],
                'Macd_base_open_blabla' => [
                    'values' => [1, 2, 3, ...]
                ]
            ]
        ]
        */
        $groups = $this->getInputGroups();

        $input = [];
        $in_sample_size = $out_sample_size = $this->getParam('sample_size');

        if (!$input_only) {
            $in_sample_size += $this->getParam('target_distance');
        }

        if ($in_sample_size !== ($actual_size = count($sample))) {
            error_log('Fann::sample2io() wrong sample size ('.$actual_size.' vs. '.$in_sample_size.')');
        }

        for ($i = 0; $i < $out_sample_size; $i++) {
            if ($i < $out_sample_size - 1) {
                reset($groups);
                foreach ($groups as $group_name => $group) {
                    //error_log('sample2io() group_name: '.$group_name);
                    reset($group);
                    foreach ($group as $sig => $params) {
                        $key = $sig;
                        $value = floatval($sample[$i]->$sig);
                        //if (!$value) {
                        //    error_log('sample2io() zero value for sig: '.$sig.' '.json_encode($sample[$i]));
                        //    exit();
                        //}
                        if ('ohlc' === $group_name) {
                            $key = 0;
                        }
                        if (!isset($input[$group_name][$key])) {
                            $input[$group_name][$key] = ['values' => []];
                        }
                        if ('range' === $group_name) {
                            $input[$group_name][$key] = array_merge($input[$group_name][$key], $params);
                        }
                        // inividual
                        $input[$group_name][$key]['values'][] = $value;
                        if (isset($params['to_zero'])) {
                            $input[$group_name][$key]['to_zero'] = true;
                        }
                    }
                }
                continue;
            }
            // for the last input candle, we only care about the fields which are based on "open"
            reset($groups);
            foreach ($groups as $group_name => $group) {
                reset($group);
                foreach ($group as $sig => $params) {
                    if ($this->indicatorIsBasedOn($sig, 'open')) {
                        $value = floatval($sample[$i]->$sig);
                        $key = ('ohlc' === $group_name) ? 0 : $sig;
                        $input[$group_name][$key]['values'][] = $value;
                    }
                }
            }
            $last_ohlc4 = Series::ohlc4($sample[$i]);
        }

        if ($input_only) {
            return $input;
        }
        return [$input, $last_ohlc4, Series::ohlc4($sample[count($sample)-1])];
    }


    public function normalizeInput(array $input)
    {
        // Normalize input to -1, 1
        reset($input);
        foreach ($input as $group_name => $group) {
            reset($group);
            foreach ($group as $sig => $params) {
                $min = $max = null;
                if ('range' === $group_name) {
                    $min = isset($params['min']) ? $params['min'] : null;
                    $max = isset($params['max']) ? $params['max'] : null;
                    if (is_null($min) || is_null($max)) {
                        error_log('Fann::normalizeInput() warning: min or max range is null for '.$group_name.': '.$sig);
                    }
                }
                if (is_null($min) || is_null($max)) {
                    $min = min($params['values']);
                    $max = max($params['values']);
                }
                if (isset($params['to_zero'])) {
                    if ($min < 0 && $max < 0) {
                        $max = 0;
                    } elseif ($min > 0 && $max > 0) {
                        $min = 0;
                    }
                }
                reset($params['values']);
                foreach ($params['values'] as $k => $v) {
                    $input[$group_name][$sig]['values'][$k] = Series::normalize($v, $min, $max);
                }
            }
        }

        // collapse normalized input groups
        $norm_input = [];
        reset($input);
        foreach ($input as $group_name => $group) {
            reset($group);
            foreach ($group as $sig => $params) {
                $norm_input = array_merge($norm_input, $input[$group_name][$sig]['values']);
            }
        }

        return $norm_input;
    }


    public function candlesToData(string $name, bool $force_rerun = false)
    {

        if (isset($this->_data[$name]) && !$force_rerun) {
            return true;
        }

        $this->runInputIndicators($force_rerun);

        $data = [];
        $sample_size = $this->getParam('sample_size');

        $groups = $this->getInputGroups();

        $this->resetSample();
        while ($sample = $this->nextSample()) {
            //error_log('candlesToData S: '.json_encode($sample));
            list($input, $last_ohlc4, $output) = $this->sample2io($sample);

            //error_log('candlesToData() input: '.json_encode($input));
            //error_log('candlesToData() last_ohlc4: '.json_encode($last_ohlc4));
            //error_log('candlesToData() output: '.json_encode($output));
            //exit();

            $input = $this->normalizeInput($input);
            //error_log('candlesToData() norm_input: '.json_encode($input));

            // output is delta of last input and output scaled
            $delta = $output - $last_ohlc4;
            //error_log($delta);
            $output = $delta * 100 / $last_ohlc4 / $this->getParam('output_scaling');
            if ($output > 1) {
                $output = 1;
            } elseif ($output < -1) {
                $output = -1;
            }
            //error_log('candlesToData() output: '.$output);

            $data[] = ['input'  => $input, 'output' => [$output]];
        }

        $this->_data[$name] = $data;
        //error_log('candlesToData() '.json_encode($data));
        return true;
    }


    public function test()
    {
        $this->candlesToData('test');
        $this->_callback_type = 'test';
        $this->_callback_iterator = 0;
        $test_data = fann_create_train_from_callback(
            count($this->_data['test']),
            $this->getNumInput(),
            $this->getParam('num_output'),
            [$this, 'createCallback']
        );

        $mse = fann_test_data($this->getFann(), $test_data);
        //$bit_fail = fann_get_bit_fail($this->getFann());
        //echo '<br />MSE: '.$mse.' Bit fail: '.$bit_fail.'<br />';
        return $mse;
    }


    public function train($max_epochs = 5000)
    {

        $t = time();
        $this->candlesToData('train'); //echo " DEBUG stop that train\n"; return false;
        $this->_callback_type = 'train';
        $this->_callback_iterator = 0;
        $training_data = fann_create_train_from_callback(
            count($this->_data['train']),
            $this->getNumInput(),
            $this->getParam('num_output'),
            [$this, 'createCallback']
        );
        //fann_save_train($training_data, BASE_PATH.'/fann/train.dat');

        $desired_error = 0.0000001;

        /* Fixed topology */
        $epochs_between_reports = 0;

        /* Cascade */
        $max_neurons = $max_epochs / 10;
        if ($max_neurons < 1) {
            $max_neurons = 1;
        }
        if ($max_neurons > 1000) {
            $max_neurons = 1000;
        }
        $neurons_between_reports = 0;

        //echo 'Training... '; flush();
        if ($this->getParam('fann_type') === 'fixed') {
            $res = fann_train_on_data(
                $this->getFann(),
                $training_data,
                $max_epochs,
                $epochs_between_reports,
                $desired_error
            );
        } elseif ($this->getParam('fann_type') === 'cascade') {
            $res = fann_cascadetrain_on_data(
                $this->getFann(),
                $training_data,
                $max_neurons,
                $neurons_between_reports,
                $desired_error
            );
        } else {
            throw new \Exception('Unknown fann type.');
        }

        $this->_bias = null;

        if ($res) {
            //echo 'done in '.(time()-$t).'s. Connections: '.count(fann_get_connection_array($this->getFann())).
            //      ', MSE: '.fann_get_MSE($this->getFann()).'<br />';
            return true;
        }
        return false;
    }


    public function createCallback($num_data, $num_input, $num_output)
    {

        if (!$this->_callback_type) {
            throw new \Exception('callback type not set');
        }

        $this->_callback_iterator++;
        return is_array($this->_data[$this->_callback_type][$this->_callback_iterator-1]) ?
            $this->_data[$this->_callback_type][$this->_callback_iterator-1] :
            false;
    }




    /** Mean Squared Error Reciprocal */
    public function getMSER()
    {
        $mse = fann_get_MSE($this->getFann());
        //error_log('MSE: '.$mse);
        if ($mse) {
            return 1 / $mse;
        }
        return 0;
    }


    public function getSampleSize()
    {
        return $this->getParam('sample_size');
    }


    public function setSampleSize(int $num)
    {
        $this->setParam('sample_size', $num);
        return $this;
    }


    public function getNumInput()
    {
        static $cache = null;

        if (!is_null($cache)) {
            return $cache;
        }

        $input_count = count($inputs = $this->getParam('inputs', []));
        $input_count_based_on_open = 0;
        foreach ($inputs as $sig) {
            if ($this->indicatorIsBasedOn($sig, 'open')) {
                $input_count_based_on_open++;
            }
        }

        $cache = ($this->getParam('sample_size') - 1) * $input_count + $input_count_based_on_open;
        if ($cache < 1) {
            $cache = 1;
        }
        //error_log('Fann::getNumInput() = '.$cache);
        return $cache;
    }


    public function getNumLayers()
    {
        if ($this->getParam('fann_type') === 'fixed') {
            return count($this->getParam('hidden_array')) + 2;
        }
        if ($this->getParam('fann_type') === 'cascade') {
            return 2;
        }
        throw new \Exception('Unknown fann type');
    }


    public function path()
    {
        $dir = $this->getParam('path');
        if (!is_dir($dir)) {
            if (!mkdir($dir)) {
                throw new \Exception('Failed to create '.$dir);
            }
            if (!chmod($dir, 0775)) {
                throw new \Exception('Failed to chmod '.$dir);
            }
        }
        return $dir.DIRECTORY_SEPARATOR.$this->getParam('id').'.fann';
    }


    public function hasBeenTrained()
    {
        return is_file($this->path());
    }
}
