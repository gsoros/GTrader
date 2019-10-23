<?php

namespace GTrader\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use GTrader\Strategy;
use GTrader\Series;
use GTrader\Indicator;
use GTrader\Util;
use GTrader\Chart;
use GTrader\Exchange;
//use GTrader\Strategies\FannTraining;
use GTrader\Plot;
use GTrader\Log;


/**
 * Strategy using the PHP-FANN extension.
 */
class Fann extends Strategy
{
    use Trainable;


    /**
     * Fann resource
     * @var resource
     */
    protected $fannResource = null;


    /**
     * Training data
     * @var array
     */
    protected $fannData = [];


    /**
     * Current sample index
     * @var int
     */
    protected $sampleIterator = 0;


    /**
     * Callback type
     * @var string
     */
    protected $callbackType;


    /**
     * Current callback index
     * @var int
     */
    protected $callbackIterator = 0;


    /**
     * Fann output on the null sample
     * @var float|null
     */
    protected $fannBias = null;


    /**
     * Adds an initial OHLC indicator and an input.
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        if (!extension_loaded('fann')) {
            throw new \Exception('FANN extension not loaded');
        }
        parent::__construct($params);
        $this->setParam('num_output', 1);
        $ohlc = $this->addIndicator('Ohlc', [], ['display' => ['visible' => true]]);
        $this->setParam('inputs', [
            $ohlc->getSignature('open'),
        ]);
        $ohlc->addRef('root');
    }


    /**
     * @return array
     */
    public function __sleep()
    {
        $this->destroyFann();
        return ['params', 'indicators'];
    }


    /**
     * Re-creates the strategy.
     */
    public function __wakeup()
    {
        parent::__wakeup();
        if (defined('FANN_WAKEUP_PREFERRED_SUFFX')) {
            //Log::debug('hacked path: '.$this->path().FANN_WAKEUP_PREFERRED_SUFFX);
            $this->loadOrCreateFann(FANN_WAKEUP_PREFERRED_SUFFX);
            return;
        }
        //Log::debug('path: '.$this->path());
        $this->loadOrCreateFann();
    }


    public function toHTML(string $content = null)
    {
        return parent::toHTML(
            view(
                'Strategies/'.$this->getShortClass().'Form',
                [
                    'strategy' => $this,
                    'injected' => $content,
                ]
            )
        );
    }



    /**
     * Returns a plot of a sample.
     * @param  int    $width    Plot width
     * @param  int    $height   Plot height
     * @param  int    $time     UTS of the last candle in the sample
     * @return string
     */
    public function getSamplePlot(int $width, int $height, int $time)
    {
        $data = [];
        $candles = $this->getCandles();
        $this->runInputIndicators();
        $sample_size = $this->getParam('sample_size');
        $target_distance = $this->getParam('target_distance');
        $resolution = $candles->getParam('resolution');
        $t = $time - $sample_size * $resolution;
        $this->resetSampleTo($t);
        if (!$sample = $this->nextSample($sample_size)) {
            // Try one candle earlier
            if ($s = $this->getSamplePlot($width, $height, $time - $resolution)) {
                return $s;
            }
            // Try one candle later
            if ($s = $this->getSamplePlot($width, $height, $time + $resolution)) {
                return $s;
            }
            // give up
            return '';
        }
        /*
        dump('Req: '.date('Y-m-d H:i', $time).
            ' Ss: '.date('Y-m-d H:i', $sample[0]->time).
            ' Se: '.date('Y-m-d H:i', $sample[count($sample)-1]->time));
        */
        $input = $this->sample2io($sample, true);
        //dump($input);
        $input = $this->normalizeInput($input, true);
        //dump($input);
        $p = $this->getPredictionIndicator();
        $p->checkAndRun();
        $p_key = $candles->key($p->getSignature());
        $last = $candles->firstAfter($time);
        if (isset($last->$p_key)) {
            $prediction = $last->$p_key;
            $realtime = $time + $target_distance * $resolution;
            //dump(date('Y-m-d H:i', $last->time).' O: '.$last->open.
            //  ' Pred for '.date('Y-m-d H:i', $realtime).': '.$prediction);
            if ($reality = $candles->firstAfter($realtime)) {
                //dump('Reality: '.date('Y-m-d H:i', $reality->time).' O: '.$reality->open);
            }
        }
        foreach ($input as $sig => $values) {
            $label = $sig;
            $display = ['stroke' => 4];
            if ($i = $candles->getOrAddIndicator($sig)) {
                $o = Indicator::getOutputFromSignature($sig);
                $label = $i->getDisplaySignature('short').' ==> '.$o;
                $i = 2;
                $suffix = '';
                while (100 > $i && isset($data[$label.$suffix])) {
                    $suffix = ' '.$i;
                    $i++;
                }
                $label .= $suffix;
            }
            //if () {
            //$display = ['y-axis' => 'right'];
            //}
            $data[$label] = ['display' => $display, 'values' => $values];
        }
        ksort($data);
        $plot = new Plot([
            'name' => 'Sample',
            'width' => $width,
            'height' => $height,
            'data' => $data,
        ]);
        return view('Strategies/FannViewSample', [
            'plot' => $plot->toHTML(),
            'chart_name' => 'mainchart',
            'prev' => $time - $resolution,
            'now' => $time,
            'next' => $time + $resolution,
        ]);
    }


    /**
     * Update strategy with parameters supplied by the user.
     * @param  Request $request
     * @return $this
     * @todo move all handleCommandRequest(R) methods to handleRequest($command, R)
     */
    public function handleSaveRequest(Request $request)
    {
        $topology_changed = false;

        $default_inputs = ['open'];
        $inputs = $request->inputs ?? $default_inputs;
        $inputs = is_array($inputs) ? $inputs : $default_inputs;
        $inputs = count($inputs) ? $inputs : $default_inputs;
        foreach ($inputs as $k => $input) {
            if (!is_string($input)) {
                Log::error('Input not a string:', $input);
                $inputs[$k] = strval($input);
            }
            $inputs[$k] = stripslashes($input);
        }
        if ($this->getParam('inputs', []) !== $inputs) {
            $topology_changed = true;
            $this->setParam('inputs', $inputs);
            Log::debug('new inputs: ', $this->getParam('inputs'));
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
            Log::info('Strategy '.$this->getParam('id').': topology changed, deleting fann.');
            $this->destroyFann();
            $this->deleteFiles();
        }

        foreach (['target_distance', 'min_trade_distance'] as $param) {
            if (isset($request->$param)) {
                $this->setParam($param, intval($request->$param));
            }
        }

        foreach (['long_threshold', 'short_threshold'] as $param) {
            if (isset($request->$param)) {
                $this->setParam($param, floatval($request->$param));
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


    /**
     * Load an existing fann network from file or create new fann network.
     * @param  string   $prefer_suffix  Filename suffix for loading the network from an alternate file.
     * @throws
     * @return $this
     */
    public function loadOrCreateFann(string $prefer_suffix = '')
    {
        if (is_resource($this->fannResource)) {
            throw new \Exception('loadOrCreateFann called but fannResource is already a resource');
        }

        $this->loadFann($this->path().$prefer_suffix);

        // create a new fann
        if (!is_resource($this->fannResource)) {
            $this->createFann();
        }
        return $this;
    }


    /**
     * Load an existing fann network from file.
     * @param  string   $prefer_suffix  Filename suffix for loading the network from an alternate file.
     * @return $this
     */
    public function loadFann($path)
    {
        if (!is_file($path) || !is_readable($path)) {
            //Log::error('cannot read '.$path);
            return $this;
        }
        //Log::info('creating fann from '.$path);
        $this->fannResource = fann_create_from_file($path);
        return $this;
    }


    /**
     * Create new fann network.
     * @throws
     * @return $this
     */
    public function createFann()
    {
        //Log::error('Fann::createFann() Input: '.$this->getNumInput());
        if ($this->getParam('fann_type') === 'fixed') {
            $params = array_merge(
                [$this->getNumLayers()],
                [$this->getNumInput()],
                $this->getParam('hidden_array'),
                [$this->getParam('num_output')]
            );
            //Log::debug('calling fann_create_shortcut('.join(', ', $params).')');
            //$this->fannResource = call_user_func_array('fann_create_standard', $params);
            $this->fannResource = call_user_func_array('fann_create_shortcut', $params);
        } elseif ($this->getParam('fann_type') === 'cascade') {
            $this->fannResource = fann_create_shortcut(
                $this->getNumLayers(),
                $this->getNumInput(),
                $this->getParam('num_output')
            );
        } else {
            throw new \Exception('Unknown fann type');
        }
        $this->initFann();
        $this->reset();
        return $this;
    }


    /**
     * Reset network weights to initial (random) values.
     * @return $this
     */
    public function reset()
    {
        fann_randomize_weights($this->fannResource, -0.77, 0.77);
        return $this;
    }


    /**
     * Initialize network.
     * @throws
     * @return $this
     */
    public function initFann()
    {
        if (!is_resource($this->fannResource)) {
            throw new \Exception('Cannot init fann, not a resource');
        }
        fann_set_activation_function_hidden($this->fannResource, FANN_SIGMOID_SYMMETRIC);
        //fann_set_activation_function_output($this->fannResource, FANN_SIGMOID_SYMMETRIC);
        //fann_set_activation_function_hidden($this->fannResource, FANN_GAUSSIAN_SYMMETRIC);
        fann_set_activation_function_output($this->fannResource, FANN_GAUSSIAN_SYMMETRIC);
        //fann_set_activation_function_hidden($this->fannResource, FANN_LINEAR);
        //fann_set_activation_function_output($this->fannResource, FANN_LINEAR);
        //fann_set_activation_function_hidden($this->fannResource, FANN_ELLIOT_SYMMETRIC);
        //fann_set_activation_function_output($this->fannResource, FANN_ELLIOT_SYMMETRIC);
        if ($this->getParam('fann_type') === 'fixed') {
            //fann_set_training_algorithm($this->fannResource, FANN_TRAIN_INCREMENTAL);
            //fann_set_training_algorithm($this->fannResource, FANN_TRAIN_BATCH);
            fann_set_training_algorithm($this->fannResource, FANN_TRAIN_RPROP);
            //fann_set_training_algorithm($this->fannResource, FANN_TRAIN_QUICKPROP);
            //fann_set_training_algorithm($this->fannResource, FANN_TRAIN_SARPROP);
        }
        //fann_set_train_error_function($this->fannResource, FANN_ERRORFUNC_LINEAR);
        fann_set_train_error_function($this->fannResource, FANN_ERRORFUNC_TANH);
        //fann_set_learning_rate($this->fannResource, 0.2);
        $this->fannBias = null;
        return $this;
    }


    /**
     * Returns the fann resource.
     * @return resource Fann resource.
     */
    public function getFann()
    {
        if (!is_resource($this->fannResource)) {
            $this->loadOrCreateFann();
        }
        return $this->fannResource;
    }


    /**
     * Returns a copy of the fann resource.
     * @return resource Fann resource.
     */
    public function copyFann()
    {
        return fann_copy($this->getFann());
    }


    /**
     * Set the fann resource.
     * @throws
     * @param resource $fann
     * @return $this
     */
    public function setFann($fann)
    {
        if (!is_resource($fann)) {
            throw new \Exception('Supplied fann is not a resource');
        }
        if ('fann' !== strtolower(get_resource_type($fann))) {
            throw new \Exception('Supplied resource is not a fann resource');
        }
        //Log::error('setFann('.get_resource_type($fann).')');
        //var_dump(debug_backtrace());
        //if (is_resource($this->fannResource)) $this->destroyFann(); // do not destroy, it may have a reference
        $this->fannResource = $fann;
        $this->initFann();
        return $this;
    }


    /**
     * Save the fann network to a file.
     * @throws
     * @param string $suffix Filename suffix
     * @return Fann $this
     */
    public function saveFann(string $suffix = '')
    {
        $fn = $this->path().$suffix;
        if (!fann_save($this->getFann(), $fn)) {
            Log::error('Saving to '.$fn.' failed');
            return $this;
        }
        if (!chmod($fn, 0664)) {
            Log::error('Chmod of '.$fn.' failed');
        }
        return $this;
    }


    /**
     * Delete strategy and its associated data.
     * @return $this
     */
    public function delete()
    {
        $this->deleteFiles();
        return parent::delete();
    }



    /**
     * Delete files associated with the strategy.
     * @return $this
     */
    public function deleteFiles()
    {
        $fann = $this->path();
        foreach ([
            $fann,
            $fann.'.train',
            storage_path('logs/'.$this->getParam('training_log_prefix').$this->getParam('id').'.log')
        ] as $file) {
            Log::info('Checking to delete '.$file);
            if (is_file($file)) {
                if (!is_writable($file)) {
                    Log::error($file.' not writable');
                    continue;
                }
                unlink($file);
            }
        }
        return $this;
    }


    /**
     * Destroy the fann resource.
     * @return $this
     */
    public function destroyFann()
    {
        if (is_resource($this->fannResource)) {
            if (!fann_destroy($this->fannResource)) {
                Log::error('Could not destroy resource.');
            }
        }
        return $this;
    }


    /**
     * Run the neural network.
     * @param array     $input          Input array
     * @param bool      $ignore_bias    Ignore bias
     * @throws
     * @return float|null
     */
    public function run($input, $ignore_bias = false)
    {
        try {
            $output = fann_run($this->getFann(), $input);
            if (!$ignore_bias) {
                $output[0] -= $this->getBias();
            }
            return $output[0];
        } catch (\Exception $e) {
            Log::error('fann_run error: '.$e->getMessage().
                ' Input layer size: '.$this->getNumInput().
                ' Inputs received: '.count($input));
            return null;
        }
    }


    /**
     * Get the network bias by running on the null sample
     * @return int Zero if bias compensation is disabled
     */
    public function getBias()
    {
        if (!$comp = $this->getParam('bias_compensation')) {
            return 0; // bias disabled
        }
        if (!is_null($this->fannBias)) {
            return $this->fannBias * $comp;
        }
        $this->fannBias = fann_run($this->getFann(), array_fill(0, $this->getNumInput(), 0))[0];
        //Log::error('bias: '.$this->fannBias);
        return $this->fannBias * $comp;
    }


    /**
     * Reset sample iterator.
     * @return $this
     */
    public function resetSample()
    {
        $this->sampleIterator = 0;
        return $this;
    }


    /**
     * Reset sample iterator to a specific time
     * @param int $time UTS
     * @return $this
     */
    public function resetSampleTo(int $time)
    {
        $k = 0;
        $this->sampleIterator = 0;
        while ($c = $this->getCandles()->byKey($k)) {
            if ($time <= $c->time) {
                $this->sampleIterator = $k;
                //dump('reset sample to '.$time.' key: '.$k, $this);
                return $this;
            }
            $k++;
        }
        //dump('warning, could not reset sample to '.$time, $this);
        return $this;
    }


    /**
     * Get the next sample
     * @param int $size optional
     * @return array|null
     */
    public function nextSample(int $size = null)
    {
        $candles = $this->getCandles();

        if (!$candles->size()) {
            return null;
        }

        if (!$size) {
            $size = $this->getParam('sample_size') + $this->getParam('target_distance');
        }

        $sample = $candles->realSlice($this->sampleIterator, $size);

        if ($size !== count($sample)) {
            return null;
        }

        $this->sampleIterator++;

        return $sample;
    }


    /**
     * Runs input indicators
     * @param bool $force_rerun Run even if already run
     * @return $this
     */
    public function runInputIndicators(bool $force_rerun = false)
    {
        $inputs = $this->getParam('inputs', []);
        $candles = $this->getCandles();
        foreach ($inputs as $sig) {
            //dump('Fann::runInputIndicators() sig: '.$sig); flush();
            if (! $indicator = $candles->getOrAddIndicator($sig)) {
                //Log::error('Could not getOrAddIndicator() '.$sig);
                continue;
            }
            $indicator->addRef('root');
            $indicator->checkAndRun($force_rerun);
            //dump('Fann::runInputIndicators() sig: '.$indicator->getSignature(), $indicator);
        }
        return $this;
    }


    /**
     * Get input indicator signatures in groups
     * @param  bool $force_rerun Do not return cached groups
     * @return array [
     *                  'ohlc' => [string $sig => true, ...],
     *                  'range' => [string $sig => ['min' => float $min, 'max' => float $max], ...],
     *                  'individual' => [string $sig => ['normalize_to' => float $norm_to], ...]
     *               ]
     * @todo needs refactoring
     */
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
            $norm_mode = $indicator = null;
            $norm_params = ['mode' => 'ohlc', 'to' => null, 'range' => ['min' => null, 'max' => null]];
            if (in_array($sig, ['open', 'high', 'low', 'close'])) {
                //Log::debug($sig.' is ohlc');
                $norm_mode = 'ohlc';
            } elseif ('volume' === $sig) {
                $norm_mode = 'individual';
                $norm_params['to'] = 0;
            } elseif (! $indicator = $this->getCandles()->getOrAddIndicator($sig)) {
                Log::error('Could not getOrAddIndicator() '.$sig);
                continue;
            }
            if (!is_null($indicator)) {
                if (! Indicator::signatureSame($sig, $indicator->getSignature())) {
                    //$m = 'Fann::getInputGroups() fatal: wanted sig '.$sig.' got '.$indicator->getSignature();
                    //Log::error($m);
                    //dd($m);
                }
                $indicator->addRef('root');
                if (!($norm_params = $indicator->getNormalizeParams())) {
                    Log::error('Could not getNormalizeParams() for '.$sig);
                    continue;
                }
                $norm_mode = $norm_params['mode'];
            }
            if ('individual' === $norm_mode) {
                $groups['individual'][$sig]['normalize_to'] = $norm_params['to'];
                continue;
            } elseif ('ohlc' === $norm_mode) {
                $groups['ohlc'][$sig] = true;
                continue;
            } elseif ('range' === $norm_mode) {
                if (is_null($min = $norm_params['range']['min']) ||
                    is_null($max = $norm_params['range']['max'])) {
                    Log::error('Min or max range not set for '.$sig);
                    continue;
                }
                $groups['range'][$sig] = ['min' => $min, 'max' => $max];
                continue;
            }
            Log::error('Unknown mode in '.json_encode($norm_params).' for '.$sig);
        }
        Util::ksortR($groups);
        //dump($groups);
        $this->cache('input_groups', $groups);
        return $groups;
    }


    /**
     * Convert a sample array to input and output array
     * @param  array  $sample
     * @param  bool   $input_only
     * @return array
     * @todo needs refactoring
     */
    public function sample2io(array $sample, bool $input_only = false)
    {
        $groups = $this->getInputGroups();
        //Log::debug('Inputs: ', $this->getParam('inputs')); exit;

        //$num_input = $this->getNumInput(); // unused

        $input = [];
        $in_sample_size = $out_sample_size = $this->getParam('sample_size');

        if (!$input_only) {
            $in_sample_size += $this->getParam('target_distance');
        }

        if ($in_sample_size !== ($actual_size = count($sample))) {
            Log::error('Wrong sample size ('.$actual_size.' vs. '.$in_sample_size.')');
        }

        for ($i = 0; $i < $out_sample_size; $i++) {
            reset($groups);
            foreach ($groups as $group_name => $group) {
                //Log::debug('group_name: '.$group_name);
                reset($group);
                foreach ($group as $sig => $params) {
                    $key = $this->getCandles()->key($sig);
                    //dump($sig.' --> '.$key);
                    if (!isset($sample[$i]->$key)) {
                        $series_sigs = [];
                        foreach ($this->getCandles()->getIndicators() as $ind) {
                            $series_sigs[] = $ind->getSignature();
                        }
                        Log::error('Value not set for key: '.$key, 'Requested sig: '.$sig, 'Series sigs:', $series_sigs, $sample[$i]);
                        exit;
                        //$value = 0;
                    } else {
                        $value = floatval($sample[$i]->$key);
                    }

                    if ($i == $out_sample_size - 1) {
                        // for the last input candle, we only include fields which are based on "open",
                        // i.e. not based on any of: high, low, close or volume
                        if ($this->indicatorOutputDependsOn($sig, ['high', 'low', 'close', 'volume'])) {
                            //Log::debug('Last candle excludes '.$sig);
                            continue;
                        }
                    }
                    if (!isset($input[$group_name][$sig])) {
                        $input[$group_name][$sig] = ['values' => []];
                    }
                    if ('ohlc' === $group_name) {

                        // Ugly, but somewhat faster without Arr::get
                        $input['ohlc']['_dim']['min'] =
                            isset($input['ohlc']['_dim']['min']) ?
                                (($min = $input['ohlc']['_dim']['min']) ?
                                    min($value, $min) :
                                    $value) :
                                $value;
                        $input['ohlc']['_dim']['max'] =
                            isset($input['ohlc']['_dim']['max']) ?
                                (($max = $input['ohlc']['_dim']['max']) ?
                                    max($value, $max) :
                                    $value) :
                                $value;

                        // $input['ohlc']['_dim']['min'] = ($min = Arr::get($input, 'ohlc._dim.min')) ?
                        //     min($value, $min) : $value;
                        // $input['ohlc']['_dim']['max'] = ($max = Arr::get($input, 'ohlc._dim.max')) ?
                        //     max($value, $max) : $value;
                    }
                    if ('range' === $group_name) {
                        $input[$group_name][$sig] = array_merge($input[$group_name][$sig], $params);
                    }
                    if (isset($params['normalize_to'])) {
                        $input[$group_name][$sig]['normalize_to'] = $params['normalize_to'];
                    }
                    if (!$value) {
                        //Log::error('Zero value for sig: '.$sig.' '.json_encode($sample[$i]));
                        //exit();
                    }
                    $input[$group_name][$sig]['values'][] = $value;
                }
            }
            $last_ohlc4 = Series::ohlc4($sample[$i]);
        }

        Util::ksortR($input);

        if ($input_only) {
            return $input;
        }
        return [$input, $last_ohlc4, Series::ohlc4($sample[count($sample)-1])];
    }


    /**
     * Normalize input array.
     * @param  array  $input
     * @param  bool   $assoc
     * @return array
     * @todo needs refactoring
     */
    public function normalizeInput(array $input, bool $assoc = false)
    {
        $ohlc_min = Arr::get($input, 'ohlc._dim.min', 0);
        $ohlc_max = Arr::get($input, 'ohlc._dim.max', 0);
        // Normalize input to -1, 1
        $norm_input = [];
        reset($input);
        foreach ($input as $group_name => $group) {
            reset($group);
            foreach ($group as $sig => $params) {
                if ('_dim' === $sig) {
                    continue;
                }
                $min = $max = null;
                if ('range' === $group_name) {
                    $min = $params['min'] ?? null;
                    $max = $params['max'] ?? null;
                    if (is_null($min) || is_null($max)) {
                        Log::warning('Min or max range is null for '.
                            $group_name.': '.$sig);
                    }
                }
                if ('ohlc' === $group_name) {
                    $min = $ohlc_min;
                    $max = $ohlc_max;
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
                foreach ($params['values'] as $v) {
                    $value = Series::normalize($v, $min, $max);
                    if ($assoc) {
                        $norm_input[$sig][] = $value;
                        continue;
                    }
                    $norm_input[] = $value;
                }
            }
        }

        return $norm_input;
    }


    /**
     * Create the data array for the network.
     * @param  string $name        Identifier
     * @param  bool   $force_rerun
     * @return $this
     * @todo needs refactoring
     */
    public function candlesToData(string $name, bool $force_rerun = false)
    {
        if (isset($this->fannData[$name]) && !$force_rerun) {
            return $this;
        }

        $this->runInputIndicators($force_rerun);

        $data = [];
        //$sample_size = $this->getParam('sample_size'); // unused

        //$groups = $this->getInputGroups(); // unused
        //Log::debug('groups:'.json_encode($groups));

        $this->resetSample();
        while ($sample = $this->nextSample()) {
            //Log::debug('S:         '.json_encode($sample));
            list($input, $last_ohlc4, $output) = $this->sample2io($sample);

            //Log::debug('input:      '.json_encode($input));
            //Log::debug('last_ohlc4: '.json_encode($last_ohlc4));
            //Log::debug('output:     '.json_encode($output));
            //exit();

            $input = $this->normalizeInput($input);
            //if (array_diff($input, [-1,0,1]))
            //    Log::debug('norm_input:', $input);


            // output is delta of last input and output scaled
            $delta = $output - $last_ohlc4;
            //Log::debug($delta);
            $output = $delta * 100 / $last_ohlc4 / $this->getParam('output_scaling');
            if ($output > 1) {
                $output = 1;
            } elseif ($output < -1) {
                $output = -1;
            }
            //Log::debug($output);

            $data[] = ['input'  => $input, 'output' => [$output]];
        }

        $this->fannData[$name] = $data;
        //Log::debug('candlesToData() ', $data);
        return $this;
    }


    /**
     * Test the network
     * @param  bool $force_rerun
     * @return float The mean sqared error
     */
    public function test(bool $force_rerun = false)
    {
        if (! ($test_data = $this->cached('test_data')) || $force_rerun) {
            $this->candlesToData('test');
            $this->callbackType = 'test';
            $this->callbackIterator = 0;
            $test_data = fann_create_train_from_callback(
                count($this->fannData['test']),
                $this->getNumInput(),
                $this->getParam('num_output'),
                [$this, 'createCallback']
            );
            $this->cache('test_data', $test_data);
        }
        $mse = fann_test_data($this->getFann(), $test_data);
        return $mse;
    }


    /**
     * Train the network
     * @param  int    $max_epochs  maximum number of epochs
     * @param  bool   $force_rerun
     * @throws
     * @return $this
     */
    public function train(int $max_epochs = 5000, bool $force_rerun = false)
    {
        if (! ($training_data = $this->cached('training_data')) || $force_rerun) {
            $this->candlesToData('train');
            $this->callbackType = 'train';
            $this->callbackIterator = 0;
            try {
                $training_data = fann_create_train_from_callback(
                    count($this->fannData['train']),
                    $this->getNumInput(),
                    $this->getParam('num_output'),
                    [$this, 'createCallback']
                );
            } catch (\Exception $e) {
                Log::error('Exception: '.$e->getMessage());
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

        if (!$res) {
            Log::error('Training failed');
        }
        $this->fannBias = null;

        return $this;
    }


    /**
     * Used internally by train() and test()
     * @param  int $num_data
     * @param  int $num_input
     * @param  int $num_output
     * @return array
     * @todo needs refactoring
     */
    protected function createCallback($num_data, $num_input, $num_output)
    {
        if (!$this->callbackType) {
            throw new \Exception('callback type not set');
        }

        $this->callbackIterator++;
        $ret = is_array($this->fannData[$this->callbackType][$this->callbackIterator-1]) ?
            $this->fannData[$this->callbackType][$this->callbackIterator-1] :
            false;
        if (!$ret) {
            Log::error('No data for '.$this->callbackType.' '.
                ($this->callbackIterator-1));
        }
        return $ret;
    }


    /**
     *  Mean Squared Error Reciprocal
     * @return float
     */
    public function getMSER()
    {
        $mse = fann_get_MSE($this->getFann());
        //Log::debug('MSE: '.$mse);
        if ($mse) {
            return 1 / $mse;
        }
        return 0;
    }


    /**
     * Get sample size
     * @return int
     */
    public function getSampleSize()
    {
        return $this->getParam('sample_size');
    }


    /**
     * Set sample size
     * @param int $num
     * @return $this
     */
    public function setSampleSize(int $num)
    {
        $this->setParam('sample_size', $num);
        return $this;
    }


    /**
     * Get the number of imput neurons
     * @return int
     */
    public function getNumInput()
    {
        if ($cached = $this->cached('num_input')) {
            return $cached;
        }

        $input_count = count($inputs = $this->getParam('inputs', []));
        $input_count_based_on_open = 0;
        $sigs_based_on_open = [];
        foreach ($inputs as $sig) {
            if (!$this->indicatorOutputDependsOn($sig, ['high', 'low', 'close', 'volume'])) {
                $input_count_based_on_open++;
                $sigs_based_on_open[] = $sig;
            }
        }

        $n = ($this->getParam('sample_size') - 1) * $input_count + $input_count_based_on_open;
        if ($n < 1) {
            $n = 1;
        }
        //Log::debug('Based on open: '.json_encode($sigs_based_on_open));
        $this->cache('num_input', $n);
        return $n;
    }


    /**
     * Get the number of layers in the network
     * @return int
     */
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


    /**
     * Get the path to saved fann
     * @return string
     */
    public function path()
    {
        //$dir = $this->getParam('path');
        $dir = self::getClassConf(get_class($this), 'path');
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


    /**
     * Returns true if fann file exists
     * @return bool
     */
    public function hasBeenTrained()
    {
        return is_file($this->path());
    }


    /**
     * Creates and returns the prediction indicator
     * @param bool $set_id
     * @return Indicator
     */
    public function getPredictionIndicator(bool $set_id = false)
    {
        $cache_key = 'prediction_indicator';
        if ($set_id) {
            $cache_key .= '_set_id';
        }
        if ($i = $this->cached($cache_key)) {
            return $i;
        }

        $candles = $this->getCandles();

        $params = $set_id ? ['strategy_id' => $this->getParam('id')] : [];
        $pred = $candles->getOrAddIndicator(
            $this->getParam('prediction_indicator_class'),
            $params
        );
        $pred->setStrategy($this);
        $pred->addRef('root');

        if (1 < ($ema_len = $this->getParam('prediction_ema'))) {
            $ema = $candles->getOrAddIndicator('Ema', [
                'indicator' => [
                    'input_source' => $pred->getSignature($pred->getOutput()),
                    'length' => $ema_len,
                ],
            ]);
            $ema->addRef($pred);
            $ema->addRef('root');
            $pred = $ema;
        }
        $this->cache($cache_key, $pred);
        return $pred;
    }


    /**
     * Creates and returns the signals indicator
     * @param array $options
     * @return Indicator|null
     */
    public function getSignalsIndicator(array $options = [])
    {
        $cache_key = 'signals_indicator';
        if ($set_prediction_id = in_array('set_prediction_id', $options)) {
            $cache_key .= '_set_prediction_id';
        }
        if ($i = $this->cached($cache_key)) {
            return $i;
        }
        if (!$candles = $this->getCandles()) {
            Log::error('No candles');
            return null;
        }
        if (!$pred = $this->getPredictionIndicator($set_prediction_id)) {
            Log::error('Could not get Prediction');
            return null;
        }
        $pred_sig = $pred->getSignature();

        $long_thresh = $candles->getOrAddIndicator('Constant', [
            'value' => 100 + $this->getParam('long_threshold', 0.5)]);
        $long_ind = $candles->getOrAddIndicator('Operator', [
            'input_a'   => 'open',
            'operation' => 'perc',
            'input_b'   => $long_thresh->getSignature(),
        ]);

        $short_thresh = $candles->getOrAddIndicator('Constant', [
            'value' => 100 - $this->getParam('short_threshold', 0.5)]);
        $short_ind = $candles->getOrAddIndicator('Operator', [
            'input_a'   => 'open',
            'operation' => 'perc',
            'input_b'   => $short_thresh->getSignature(),
        ]);

        $long_sig = $long_ind->getSignature();
        $short_sig = $short_ind->getSignature();

        if (!$signals = $candles->getOrAddIndicator('Signals',
            [
                'strategy_id'               => 0, // Custom Settings
                'input_open_long_a'         => $long_sig,
                'open_long_cond'            => '<',
                'input_open_long_b'         => $pred_sig,
                'input_open_long_source'    => $this->getParam('long_source', 'open'),
                'input_close_long_a'        => $long_sig,
                'close_long_cond'           => '>=',
                'input_close_long_b'        => $pred_sig,
                'input_close_long_source'   => $this->getParam('long_source', 'open'),
                'input_open_short_a'        => $short_sig,
                'open_short_cond'           => '>',
                'input_open_short_b'        => $pred_sig,
                'input_open_short_source'   =>  $this->getParam('short_source', 'open'),
                'input_close_short_a'        => $short_sig,
                'close_short_cond'           => '<=',
                'input_close_short_b'        => $pred_sig,
                'input_close_short_source'   =>  $this->getParam('short_source', 'open'),
                'min_trade_distance'        =>  $this->getParam('min_trade_distance', 1),
            ])) {
            Log::error('Could not add Signals');
            return null;
        }
        $signals->addRef('root');
        $this->cache($cache_key, $signals);
        return $signals;
    }


    /**
     * Called when not continuing previous training
     * @return $this
     */
    public function fromScratch()
    {
        $this->destroyFann();
        $this->deleteFiles();
        $this->loadOrCreateFann();
        $this->deleteHistory();
        return $this;
    }
}
