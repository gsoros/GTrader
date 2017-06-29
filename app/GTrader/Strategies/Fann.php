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
        $chart = Chart::make(null, [
            'candles' => $candles,
            'name' => 'trainingChart',
            'height' => 200,
            'disabled' => ['title', 'map', 'panZoom', 'strategy', 'settings'],
        ]);
        $ind = $chart->addIndicator('Ohlc');
        $ind->setParam('display.visible', true);
        $ind->addRef($chart);
        $chart->saveToSession();
        return $chart;
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

        $chart = Chart::make(null, [
            'candles' => $candles,
            'strategy' => $this,
            'name' => 'trainingProgressChart',
            'height' => 200,
            'disabled' => ['title', 'strategy', 'map', 'settings'],
            'readonly' => ['esr'],
            'highlight' => $highlights,
            'visible_indicators' => ['Ohlc', 'Balance', 'Profitability'],
        ]);
        $ind = $chart->addIndicator('Ohlc');
        $ind->setParam('display.visible', true);
        $ind->addRef($chart);

        $sig = $training->getMaximizeSig();
        if (!$chart->hasIndicator($sig)) {
            $ind = $chart->addIndicatorBySignature($sig);
            $ind->setParam('display.visible', true);
            $ind->addRef($chart);
            $this->save();
        }

        if (!$chart->hasIndicatorClass('Balance')) {
            $ind = $chart->addIndicator('Balance');
            $ind->setParam('display.visible', true);
            $ind->addRef($chart);
            $this->save();
        }
        $ind = $chart->getFirstIndicatorByClass('Balance');
        $ind->setParam('display.visible', true);

        if (!$chart->hasIndicatorClass('Profitability')) {
            $ind = $chart->addIndicator('Profitability');
            $ind->addRef($chart);
            $this->save();
        }

        $chart->saveToSession();

        return $chart;
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
                $display = [];
                if ('train_mser' === $item->name) {
                    $display = ['y_axis_pos' => 'right'];
                }
                $data[$item->name] = ['display' => $display, 'values' => []];
            }
            $data[$item->name]['values'][$item->epoch] = $item->value;
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
        foreach ($inputs as $k => $input) {
            if (!is_string($input)) {
                error_log('Fann::handleSaveRequest() input not a string: '.json_encode($input));
                $inputs[$k] = strval($input);
            }
        }
        if ($this->getParam('inputs', []) !== $inputs) {
            $topology_changed = true;
            $this->setParam('inputs', $inputs);
            error_log('Fann::handleSaveRequest() new inputs: '.json_encode($this->getParam('inputs')));
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
            if ($sample_size < 1) {
                $sample_size = 1;
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

        foreach ([
            'target_distance',
            'long_threshold',
            'short_threshold',
            'min_trade_distance'
        ] as $param) {
            if (isset($request->$param)) {
                $this->setParam($param, intval($request->$param));
            }
        }

        foreach (['long_source', 'short_source'] as $param) {
            if (isset($request->$param)) {
                $this->setParam($param, $request->$param);
            }
        }

        parent::handleSaveRequest($request);
        return $this;
    }


    public function listItem()
    {
        try {
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

            $html = view(
                'Strategies/FannListItem',
                [
                    'strategy' => $this,
                    'training_status' => $training_status
                ]
            );
        } catch (\Exception $e) {
            error_log('Fann::listItem() failed for id '.$this->getParam('id'));
            $html = '[Failed to display FannListItem]';
        }
        return $html;
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
            $indicator = Indicator::make($class);
            $candles->addIndicator($indicator);
        }

        $ema_len = $this->getParam('prediction_ema');
        if ($ema_len > 1) {
            $indicator = Indicator::make(
                'Ema', [
                    'indicator' => [
                        'input_source' => $indicator->getSignature(),
                        'length' => $ema_len,
                    ],
                    'depends' => [$indicator],
                ]
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
            error_log('fann_run error: '.$e->getMessage().
                        ' Input: '.serialize($input));
            exit;
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
        return true;
    }


    public function nextSample($size = null)
    {
        $candles = $this->getCandles();

        if (!$candles->size()) {
            return null;
        }

        if (!$size) {
            $size = $this->getParam('sample_size') + $this->getParam('target_distance');
        }

        $sample = $candles->realSlice($this->_sample_iterator, $size);

        if ($size !== count($sample)) {
            return null;
        }

        $this->_sample_iterator++;

        return $sample;
    }


    public function runInputIndicators(bool $force_rerun = false)
    {
        $inputs = $this->getParam('inputs', []);
        $candles = $this->getCandles();
        foreach ($inputs as $sig) {
            if (! $indicator = $candles->getOrAddIndicator($sig)) {
                //error_log('runInputIndicators() could not getOrAddIndicator() '.$sig);
                continue;
            }
            $indicator->addRef($this);
            $indicator->checkAndRun($force_rerun);
        }
        return $this;
    }


    public function getInputGroups(bool $force_rerun = false)
    {
        //$this->setParam('cache.log', 'put, miss');
        if (!$force_rerun) {
            if (($groups = $this->cached('input_groups'))) {
                return $groups;
            }
        }

        $inputs = $this->getParam('inputs', []);
        $groups = [];

        reset($inputs);
        foreach ($inputs as $sig) {
            $norm_mode = $norm_to = $indicator = null;
            $output = '';
            $naked_sig = $sig;
            $norm_params = ['mode' => 'ohlc', 'to' => null, 'range' => ['min' => null, 'max' => null]];
            if (in_array($sig, ['open', 'high', 'low', 'close'])) {
                //error_log('Fann::getInputGroups() '.$sig.' is ohlc');
                $norm_mode = 'ohlc';
            }
            elseif ('volume' === $sig) {
                $norm_mode = 'individual';
            }
            elseif (! $indicator = $this->getCandles()->getOrAddIndicator($sig)) {
                error_log('Fann::getInputGroups() could not getOrAddIndicator() '.$sig);
                continue;
            }
            if (!is_null($indicator)) {
                if (! Indicator::signatureSame($sig, $indicator->getSignature())) {
                    error_log('Fann::getInputGroups() fatal: wanted sig '.$sig.' got '.$indicator->getSignature());
                    exit;
                }
                $indicator->addRef($this);
                if (!($norm_params = $indicator->getNormalizeParams())) {
                    error_log('Fann::getInputGroups() could not getNormalizeParams() for '.$sig);
                    continue;
                }
                $norm_mode = $norm_params['mode'];
                $output = Indicator::getOutputFromSignature($sig);
                // sig str without output
                $naked_sig = $indicator->getSignature();
            }
            if ('individual' === $norm_mode) {
                $norm_to = $norm_params['to'];
            }
            else if ('ohlc' === $norm_mode) {
                $groups['ohlc'][$sig] = true;
                continue;
            }
            else if ('range' === $norm_mode) {
                if (is_null($min = $norm_params['range']['min']) ||
                    is_null($max = $norm_params['range']['max'])) {
                    error_log('Fann::getInputGroups() min or max range not set for '.$sig);
                    continue;
                }
                $groups['range'][$sig] = ['min' => $min, 'max' => $max];
                continue;
            }
            if ('individual' === $norm_mode) {
                if (!is_null($norm_to) && !isset($groups['individual'][$naked_sig]['normalize_to'])) {
                    $groups['individual'][$naked_sig]['normalize_to'] = $norm_to;
                }
                $groups['individual'][$naked_sig]['outputs'][] = $output;
                continue;
            }
            error_log('Fann::getInputGroups() unknown mode in '.json_encode($norm_params).' for '.$sig);
        }
        //echo 'getInputGroups() groups: '; print_r($groups); exit;
        $this->cache('input_groups', $groups);
        return $groups;
    }


    public function sample2io(array $sample, bool $input_only = false) {

        $groups = $this->getInputGroups();
        //error_log('Fann::sample2io() inputs: '.json_encode($this->getParam('inputs'))); exit;

        $num_input = $this->getNumInput();

        $input = [];
        $in_sample_size = $out_sample_size = $this->getParam('sample_size');

        if (!$input_only) {
            $in_sample_size += $this->getParam('target_distance');
        }

        if ($in_sample_size !== ($actual_size = count($sample))) {
            error_log('Fann::sample2io() wrong sample size ('.$actual_size.' vs. '.$in_sample_size.')');
        }

        //$dumptime = strtotime('2017-06-11 10:00:00');

        for ($i = 0; $i < $out_sample_size; $i++) {
            reset($groups);
            foreach ($groups as $group_name => $group) {
                //error_log('sample2io() group_name: '.$group_name);
                reset($group);
                foreach ($group as $sig => $params) {
                    $sig_key = $this->getCandles()->key($sig);
                    //if ($dumptime == $sample[$i]->time) {
                    //    error_log('Fann::sample2io() params: '.json_encode($params));
                    //}
                    if ($i == $out_sample_size - 1) {
                        // for the last input candle, we only include fields which are based on "open",
                        // i.e. not based on any of: high, low, close or volume
                        if ($this->indicatorHasInput($sig, ['high', 'low', 'close', 'volume'])) {
                            //error_log('Fann::sample2io() last candle excludes '.$sig);
                            continue;
                        }
                    }
                    $key = $sig;
                    if ('ohlc' === $group_name) {
                        $key = 0;
                    }
                    if (!isset($input[$group_name][$key])) {
                        $input[$group_name][$key] = ['values' => []];
                    }
                    if ('range' === $group_name) {
                        $input[$group_name][$key] = array_merge($input[$group_name][$key], $params);
                    }
                    if (isset($params['normalize_to'])) {
                        $input[$group_name][$key]['normalize_to'] = $params['normalize_to'];
                    }
                    // inividual
                    if (isset($params['outputs'])) {
                        if (is_array($params['outputs'])) {
                            $outputs_added = 0;
                            foreach ($params['outputs'] as $output_name) {
                                $sig_key_output = $sig_key;
                                if ($output_name) {
                                    $outputs_added++;
                                    $sig_key_output = $this->getCandles()->key($key.':::'.$output_name);
                                    $value = floatval($sample[$i]->$sig_key_output);
                                    $input[$group_name][$key]['values'][] = $value;
                                }
                            }
                            if ($outputs_added) {
                                continue;
                            }
                        }
                    }
                    $value = floatval($sample[$i]->$sig_key);
                    if (!$value) {
                        //error_log('sample2io() zero value for sig: '.$sig.' '.json_encode($sample[$i]));
                        //exit();
                    }
                    $input[$group_name][$key]['values'][] = $value;
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
                if (isset($params['normalize_to'])) {
                    $to = $params['normalize_to'];
                    if ($min < $to && $max < $to) {
                        $max = $to;
                    } elseif ($min > $to && $max > $to) {
                        $min = $to;
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
        //error_log('candlesToData() groups:'.json_encode($groups));

        $this->resetSample();
        while ($sample = $this->nextSample()) {
            //error_log('candlesToData S: '.json_encode($sample));
            list($input, $last_ohlc4, $output) = $this->sample2io($sample);

            //error_log('candlesToData() input:      '.json_encode($input));
            //error_log('candlesToData() last_ohlc4: '.json_encode($last_ohlc4));
            //error_log('candlesToData() output: '.json_encode($output));
            //exit();

            $input = $this->normalizeInput($input);
            //if (array_diff($input, [-1,0,1]))
            //    error_log('candlesToData() norm_input: '.json_encode($input));


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


    public function test(bool $force_rerun = false)
    {
        if (! ($test_data = $this->cached('test_data')) || $force_rerun) {
            $this->candlesToData('test');
            $this->_callback_type = 'test';
            $this->_callback_iterator = 0;
            $test_data = fann_create_train_from_callback(
                count($this->_data['test']),
                $this->getNumInput(),
                $this->getParam('num_output'),
                [$this, 'createCallback']
            );
            $this->cache('test_data', $test_data);
        }
        $mse = fann_test_data($this->getFann(), $test_data);
        return $mse;
    }


    public function train(int $max_epochs = 5000, bool $force_rerun = false)
    {
        if (! ($training_data = $this->cached('training_data')) || $force_rerun) {
            $this->candlesToData('train');
            $this->_callback_type = 'train';
            $this->_callback_iterator = 0;
            try {
                $training_data = fann_create_train_from_callback(
                    count($this->_data['train']),
                    $this->getNumInput(),
                    $this->getParam('num_output'),
                    [$this, 'createCallback']
                );
            } catch (\Exception $e) {
                error_log('Fann::train() Exception: '.$e->getMessage());
                exit;
            }
            $this->cache('training_data', $training_data);
        }
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

        return $res ? true : false;
    }


    public function createCallback($num_data, $num_input, $num_output)
    {

        if (!$this->_callback_type) {
            throw new \Exception('callback type not set');
        }

        $this->_callback_iterator++;
        $ret = is_array($this->_data[$this->_callback_type][$this->_callback_iterator-1]) ?
            $this->_data[$this->_callback_type][$this->_callback_iterator-1] :
            false;
        if (!$ret) {
            error_log('Fann::createCallback() nodata for '.$this->_callback_type.' '.
                ($this->_callback_iterator-1));
        }
        return $ret;
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
        if ($cached = $this->cached('num_input')) {
            return $cached;
        }

        $input_count = count($inputs = $this->getParam('inputs', []));
        $input_count_based_on_open = 0;
        $sigs_based_on_open = [];
        foreach ($inputs as $sig) {
            if (!$this->indicatorHasInput($sig, ['high', 'low', 'close', 'volume'])) {
                $input_count_based_on_open++;
                $sigs_based_on_open[] = $sig;
            }
        }

        $n = ($this->getParam('sample_size') - 1) * $input_count + $input_count_based_on_open;
        if ($n < 1) {
            $n = 1;
        }
        //error_log('Fann::getNumInput() based on open: '.json_encode($sigs_based_on_open));
        $this->cache('num_input', $n);
        return $n;
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
