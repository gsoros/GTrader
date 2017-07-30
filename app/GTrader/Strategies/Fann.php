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
use GTrader\FannTraining;
use GTrader\Plot;

if (!extension_loaded('fann')) {
    throw new \Exception('FANN extension not loaded');
}

/**
 * Strategy using the PHP-FANN extension.
 */
class Fann extends Strategy
{
    /**
     * Fann resource
     * @var resource
     */
    protected $_fann = null;

    /**
     * Training data
     * @var array
     */
    protected $_data = [];

    /**
     * Current sample index
     * @var int
     */
    protected $_sample_iterator = 0;

    /**
     * Callback type
     * @var string
     */
    protected $_callback_type;

    /**
     * Current callback index
     * @var int
     */
    protected $_callback_iterator = 0;

    /**
     * Fann output on the null sample
     * @var float
     */
    protected $_bias = null;

    /**
     * Adds an initial OHLC indicator and an input.
     * @param array $params
     */
    public function __construct(array $params = [])
    {
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
            //error_log('Fann::__wakeup() Hacked path: '.$this->path().FANN_WAKEUP_PREFERRED_SUFFX);
            $this->loadOrCreateFann(FANN_WAKEUP_PREFERRED_SUFFX);
        } else {
            //error_log('Fann::__wakeup() path: '.$this->path());
            $this->loadOrCreateFann();
        }
    }

    /**
     * Returns the HTML form representation.
     * @param  string $content not used
     * @return string
     */
    public function toHTML(string $content = null)
    {
        return parent::toHTML(
            view('Strategies/'.$this->getShortClass().'Form', ['strategy' => $this])
        );
    }

    /**
     * Training chart for selecting the ranges.
     * @return Chart
     */
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
        $ind = $chart->addIndicator('Ohlc', ['mode' => 'linepoints']);
        $ind->visible(true);
        $ind->addRef('root');
        $chart->saveToSession();
        return $chart;
    }

    /**
     * Training progress chart.
     * @param FannTraining  $training
     * @return Chart
     */
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
            'disabled' => ['title', 'strategy', 'map', 'settings', 'fullscreen'],
            'readonly' => ['esr'],
            'highlight' => $highlights,
            'visible_indicators' => ['Ohlc', 'Balance', 'Profitability'],
        ]);
        $ind = $chart->getOrAddIndicator('Ohlc', ['mode' => 'linepoints']);
        $ind->visible(true);
        $ind->addRef('root');

        $sig = $training->getMaximizeSig($this);
        $ind = $chart->getOrAddIndicator($sig);
        $ind->visible(true);
        $ind->addRef('root');

        if (!$ind = $chart->getFirstIndicatorByClass('Balance')) {
            $ind = $chart->getOrAddIndicator('Balance');
        }
        $ind->visible(true);
        $ind->addRef('root');

        $signal_sig = $this->getSignalsIndicator()->getSignature();
        if (!$chart->hasIndicatorClass('Profitability')) {
            $ind = $chart->getOrAddIndicator('Profitability', [
                'input_signal' => $signal_sig,
            ]);
            $ind->visible(true);
            $ind->addRef('root');
        }

        $chart->saveToSession();
        return $chart;
    }

    /**
     * Returns a plot of the training history.
     * @param  int    $width    Plot width
     * @param  int    $height   Plot height
     * @return string
     */
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
            $name = ucfirst(str_replace('_', ' ', $item->name));
            if (!array_key_exists($name, $data)) {
                $display = [];
                if ('train_mser' === $item->name) {
                    $display = ['y-axis' => 'right'];
                }
                $data[$name] = ['display' => $display, 'values' => []];
            }
            $data[$name]['values'][$item->epoch] = $item->value;
        }
        ksort($data);
        $plot = new Plot([
            'name' => 'History',
            'width' => $width,
            'height' => $height,
            'data' => $data,
        ]);
        return $plot->toHTML();
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
            //dump(date('Y-m-d H:i', $last->time).' O: '.$last->open.' Pred for '.date('Y-m-d H:i', $realtime).': '.$prediction);
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
        $inputs = isset($request->inputs) ? $request->inputs : $default_inputs;
        $inputs = is_array($inputs) ? $inputs : $default_inputs;
        $inputs = count($inputs) ? $inputs : $default_inputs;
        foreach ($inputs as $k => $input) {
            if (!is_string($input)) {
                error_log('Fann::handleSaveRequest() input not a string: '.json_encode($input));
                $inputs[$k] = strval($input);
            }
            $inputs[$k] = stripslashes($input);
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
     * Display strategy as list item.
     * @return string
     */
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


    /**
     * Load an existing fann network from file or create new fann network.
     * @param  string   $prefer_suffix  Filename suffix for loading the network from an alternate file.
     * @throws
     * @return $this
     */
    public function loadOrCreateFann(string $prefer_suffix = '')
    {
        if (is_resource($this->_fann)) {
            throw new \Exception('loadOrCreateFann called but _fann is already a resource');
        }

        $this->loadFann($this->path().$prefer_suffix);

        // create a new fann
        if (!is_resource($this->_fann)) {
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
            //error_log('loadFann() cannot read '.$path);
            return $this;
        }
        //error_log('creating fann from '.$path);
        $this->_fann = fann_create_from_file($path);
        return $this;
    }

    /**
     * Create new fann network.
     * @throws
     * @return $this
     */
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
        return $this;
    }


    /**
     * Reset network weights to initial (random) values.
     * @return $this
     */
    public function reset()
    {
        fann_randomize_weights($this->_fann, -0.77, 0.77);
        return $this;
    }


    /**
     * Initialize network.
     * @throws
     * @return $this
     */
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
        return $this;
    }

    /**
     * Returns the fann resource.
     * @return resource Fann resource.
     */
    public function getFann()
    {
        if (!is_resource($this->_fann)) {
            $this->loadOrCreateFann();
        }
        return $this->_fann;
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
    public function setFann(resource $fann)
    {
        if (!is_resource($fann)) {
            throw new \Exception('Supplied fann is not a resource');
        }
        if ('fann' !== get_resource_type($fann)) {
            throw new \Exception('Supplied resource is not a fann resource');
        }
        //error_log('setFann('.get_resource_type($fann).')');
        //var_dump(debug_backtrace());
        //if (is_resource($this->_fann)) $this->destroyFann(); // do not destroy, it may have a reference
        $this->_fann = $fann;
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
            error_log('Fann::saveFann() saving to '.$fn.' failed');
            return $this;
        }
        if (!chmod($fn, 0664)) {
            error_log('Fann::saveFann() chmod of '.$fn.' failed');
        }
        return $this;
    }


    /**
     * Delete strategy and its associated data.
     * @return $this
     */
    public function delete()
    {
        // delete trainings
        FannTraining::where('strategy_id', $this->getParam('id'))->delete();
        // delete files
        $this->deleteFiles();
        // delete training history
        $this->deleteHistory();
        // delete strategy
        return parent::delete();
    }


    /**
     * Delete training history.
     * @return $this
     */
    public function deleteHistory()
    {
        $affected = DB::table('fann_history')
            ->where('strategy_id', $this->getParam('id'))
            ->delete();
        error_log('Fann::deleteHistory() '.$affected.' records deleted.');
        return $this;
    }

    /**
     * Save training history item.
     * @param  int    $epoch Training epoch
     * @param  string $name  Item name
     * @param  float  $value Item value
     * @return $this
     */
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

    /**
     * Get number of history records.
     * @return int Number of records
     */
    public function getHistoryNumRecords()
    {
        return DB::table('fann_history')
            ->where('strategy_id', $this->getParam('id'))
            ->count();
    }

    /**
     * Remove every nth training history record.
     * @param  integer $nth
     * @return $this
     */
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

    /**
     * @return int Epoch
     */
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

    /**
     * Destroy the fann resource.
     * @return $this
     */
    public function destroyFann()
    {
        if (is_resource($this->_fann)) {
            if (!fann_destroy($this->_fann)) {
                error_log('Fann::destroyFann() could not destroy resource.');
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
            error_log('fann_run error: '.$e->getMessage().
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
        if (!is_null($this->_bias)) {
            return $this->_bias * $comp;
        }
        $this->_bias = fann_run($this->getFann(), array_fill(0, $this->getNumInput(), 0))[0];
        //error_log('bias: '.$this->_bias);
        return $this->_bias * $comp;
    }


    /**
     * Reset sample iterator.
     * @return $this
     */
    public function resetSample()
    {
        $this->_sample_iterator = 0;
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
        $this->_sample_iterator = 0;
        while ($c = $this->getCandles()->byKey($k)) {
            if ($time <= $c->time) {
                $this->_sample_iterator = $k;
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

        $sample = $candles->realSlice($this->_sample_iterator, $size);

        if ($size !== count($sample)) {
            return null;
        }

        $this->_sample_iterator++;

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
                //error_log('runInputIndicators() could not getOrAddIndicator() '.$sig);
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
                //error_log('Fann::getInputGroups() '.$sig.' is ohlc');
                $norm_mode = 'ohlc';
            } elseif ('volume' === $sig) {
                $norm_mode = 'individual';
                $norm_params['to'] = 0;
            } elseif (! $indicator = $this->getCandles()->getOrAddIndicator($sig)) {
                error_log('Fann::getInputGroups() could not getOrAddIndicator() '.$sig);
                continue;
            }
            if (!is_null($indicator)) {
                if (! Indicator::signatureSame($sig, $indicator->getSignature())) {
                    //$m = 'Fann::getInputGroups() fatal: wanted sig '.$sig.' got '.$indicator->getSignature();
                    //error_log($m);
                    //dd($m);
                }
                $indicator->addRef('root');
                if (!($norm_params = $indicator->getNormalizeParams())) {
                    error_log('Fann::getInputGroups() could not getNormalizeParams() for '.$sig);
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
                    error_log('Fann::getInputGroups() min or max range not set for '.$sig);
                    continue;
                }
                $groups['range'][$sig] = ['min' => $min, 'max' => $max];
                continue;
            }
            error_log('Fann::getInputGroups() unknown mode in '.json_encode($norm_params).' for '.$sig);
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
                    $key = $this->getCandles()->key($sig);
                    if (isset($sample[$i]->$key)) {
                        $value = floatval($sample[$i]->$key);
                    } else {
                        error_log('Fann::sample2io() value not set for key:'.$key.' sig:'.$sig);
                        $value = 0;
                    }

                    if ($i == $out_sample_size - 1) {
                        // for the last input candle, we only include fields which are based on "open",
                        // i.e. not based on any of: high, low, close or volume
                        if ($this->indicatorOutputDependsOn($sig, ['high', 'low', 'close', 'volume'])) {
                            //error_log('Fann::sample2io() last candle excludes '.$sig);
                            continue;
                        }
                    }
                    if (!isset($input[$group_name][$sig])) {
                        $input[$group_name][$sig] = ['values' => []];
                    }
                    if ('ohlc' === $group_name) {
                        $input['ohlc']['_dim']['min'] = ($min = Arr::get($input, 'ohlc._dim.min')) ?
                            min($value, $min) : $value;
                        $input['ohlc']['_dim']['max'] = ($max = Arr::get($input, 'ohlc._dim.max')) ?
                            max($value, $max) : $value;
                    }
                    if ('range' === $group_name) {
                        $input[$group_name][$sig] = array_merge($input[$group_name][$sig], $params);
                    }
                    if (isset($params['normalize_to'])) {
                        $input[$group_name][$sig]['normalize_to'] = $params['normalize_to'];
                    }
                    if (!$value) {
                        //error_log('sample2io() zero value for sig: '.$sig.' '.json_encode($sample[$i]));
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
                    $min = isset($params['min']) ? $params['min'] : null;
                    $max = isset($params['max']) ? $params['max'] : null;
                    if (is_null($min) || is_null($max)) {
                        error_log('Fann::normalizeInput() warning: min or max range is null for '.$group_name.': '.$sig);
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
                foreach ($params['values'] as $k => $v) {
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
        if (isset($this->_data[$name]) && !$force_rerun) {
            return $this;
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
        return $this;
    }


    /**
     * Test the network
     * @param  bool $force_rerun
     * @return int The mean sqared error
     */
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

        if (!$res) {
            error_log('Fann::train() training failed');
        }
        $this->_bias = null;

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
        if (!$this->_callback_type) {
            throw new \Exception('callback type not set');
        }

        $this->_callback_iterator++;
        $ret = is_array($this->_data[$this->_callback_type][$this->_callback_iterator-1]) ?
            $this->_data[$this->_callback_type][$this->_callback_iterator-1] :
            false;
        if (!$ret) {
            error_log('Fann::createCallback() no data for '.$this->_callback_type.' '.
                ($this->_callback_iterator-1));
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
        //error_log('MSE: '.$mse);
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
        //error_log('Fann::getNumInput() based on open: '.json_encode($sigs_based_on_open));
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
     * @return Indicator
     */
    public function getPredictionIndicator()
    {
        if ($i = $this->cached('prediction_indicator')) {
            return $i;
        }

        $candles = $this->getCandles();

        $pred = $candles->getOrAddIndicator(
            $this->getParam('prediction_indicator_class')
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
        $this->cache('prediction_indicator', $pred);
        return $pred;
    }


    /**
     * Creates and returns the signals indicator
     * @return Indicator|null
     */
    public function getSignalsIndicator()
    {
        if ($i = $this->cached('signals_indicator')) {
            return $i;
        }
        if (!$candles = $this->getCandles()) {
            error_log('Fann::getSignalsIndicator() no candles');
            return null;
        }
        if (!$pred = $this->getPredictionIndicator()) {
            error_log('Fann::getSignalsIndicator() could not get Prediction');
            return null;
        }
        $pred_sig = $pred->getSignature();

        $long_thresh = $candles->getOrAddIndicator('Constant', [
            'value' => 100 + $this->getParam('long_threshold', 0.5)]);
        $long_ind = $candles->getOrAddIndicator('Operator', [
            'input_a' => 'open',
            'operation' => 'perc',
            'input_b' => $long_thresh->getSignature(),
        ]);

        $short_thresh = $candles->getOrAddIndicator('Constant', [
            'value' => 100 - $this->getParam('short_threshold', 0.5)]);
        $short_ind = $candles->getOrAddIndicator('Operator', [
            'input_a' => 'open',
            'operation' => 'perc',
            'input_b' => $short_thresh->getSignature(),
        ]);

        if (!$signals = $candles->getOrAddIndicator('Signals', [
                'strategy_id' => 0, // Custom Settings
                'input_long_a' => $long_ind->getSignature(),
                'long_cond' => '<',
                'input_long_b' => $pred_sig,
                'input_long_source' => $this->getParam('long_source', 'open'),
                'input_short_a' => $short_ind->getSignature(),
                'short_cond' => '>',
                'input_short_b' => $pred_sig,
                'input_short_source' =>  $this->getParam('short_source', 'open'),
            ])) {
            error_log('Fann::getSignalsIndicator() could not add Signals');
            return null;
        }
        $signals->addRef('root');
        $this->cache('signals_indicator', $signals);
        return $signals;
    }
}
