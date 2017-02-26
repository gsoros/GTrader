<?php

namespace GTrader\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use GTrader\Strategy;
use GTrader\Series;
use GTrader\Indicator;
use GTrader\Util;
use GTrader\Chart;
use GTrader\FannTraining;

if (!extension_loaded('fann'))
    throw new \Exception('FANN extension not loaded');

class Fann extends Strategy
{

    protected $_fann = null;              // fann resource
    protected $_data = [];
    protected $_pack_iterator = 0;
    protected $_callback_type = false;
    protected $_callback_iterator = 0;
    protected $_bias = null;


    public function __construct(array $params = [])
    {
        error_log('Fann::__construct()');
        parent::__construct($params);
        $this->setParam('num_output', 1);
    }


    public function __wakeup()
    {
        error_log('Fann::__wakeup()');
        $this->createFann();
    }


    public function toHTML(string $content = null)
    {
        return parent::toHTML(
                view('Strategies/'.$this->getShortClass().'Form', ['strategy' => $this]));
    }


    public function getTrainingChart()
    {
        $resolution = 0;
        $mainchart = session('mainchart');
        if (is_object($mainchart))
            $resolution = $mainchart->getCandles()->getParam('resolution');
        $candles = new Series(['limit' => 0, 'resolution' => $resolution]);
        $training_chart = Chart::make(null, [
                            'candles' => $candles,
                            'name' => 'trainingChart',
                            'height' => 200,
                            'disabled' => ['title', 'map', 'panZoom', 'strategy', 'settings']]);
        $training_chart->saveToSession();
        return $training_chart;
    }


    public function handleSaveRequest(Request $request)
    {
        foreach (['config_file', 'num_samples'] as $param)
            if (isset($request->$param))
                $this->setParam($param, $request->$param);

        parent::handleSaveRequest($request);
        return $this;
    }


    public function listItem()
    {
        return view('Strategies/FannListItem', ['strategy' => $this]);
    }


    public function getPredictionIndicator()
    {
        $class = $this->getParam('prediction_indicator_class');

        $indicator = null;
        foreach ($this->getIndicators() as $candidate)
            if ($class === $candidate->getShortClass())
                $indicator = $candidate;
        if (is_null($indicator))
        {
            $indicator = Indicator::make($class, ['display' => ['visible' => false]]);
            $this->addIndicator($indicator);
        }

        $ema_len = $this->getParam('prediction_ema');
        if ($ema_len > 1)
        {
            $candles = $this->getCandles();
            $indicator = Indicator::make('Ema',
                            ['indicator' => [   'price' => $indicator->getSignature(),
                                                'length' => $ema_len],
                             'display' => [     'visible' => false],
                             'depends' => [     $indicator]]);

            $candles->addIndicator($indicator);
            $indicator = $candles->getIndicator($indicator->getSignature());
        }

        return $indicator;
    }


    public function createFann()
    {
        if (is_resource($this->_fann))
            throw new \Exception('createFann called but _fann is already a resource');

        $path = $this->path();
        if (is_file($path))
        {
            error_log('creating fann from '.$path);
            $this->_fann = fann_create_from_file($path);
        }
        if (!is_resource($this->_fann))
        {
            if ($this->getParam('fann_type') === 'fixed')
            {
                $params = array_merge(
                            [$this->getParam('num_layers')],
                            [$this->getNumInput()],
                            $this->getParam('hidden_array'),
                            [$this->getParam('num_output')]);
                error_log('calling fann_create_standard('.join(', ', $params).')');
                $this->_fann = call_user_func_array('fann_create_standard', $params);
                //$this->_fann = call_user_func_array('fann_create_shortcut', $params);
            }
            else if ($this->getParam('fann_type') == 'cascade')
                $this->_fann = fann_create_shortcut(
                                $this->getParam('num_layers'),
                                $this->getNumInput(),
                                $this->getParam('num_output'));
            else throw new \Exception('Unknown fann type');
            //fann_randomize_weights($this->_fann, -0.2, 0.2);
        }
        $this->initFann();
        return true;
    }



    public function initFann()
    {
        if (!is_resource($this->_fann))
            throw new \Exception('Cannot init fann, not a resource');
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
            fann_set_training_algorithm($this->_fann, FANN_TRAIN_RPROP);
            //fann_set_training_algorithm($this->_fann, FANN_TRAIN_QUICKPROP);
        }
        fann_set_train_error_function($this->_fann, FANN_ERRORFUNC_LINEAR);
        //fann_set_learning_rate($this->_fann, 0.5);
        $this->_bias = null;
        return true;
    }


    public function getFann()
    {
        if (!is_resource($this->_fann)) $this->createFann();
        return $this->_fann;
    }


    public function copyFann()
    {
        return fann_copy($this->getFann());
    }


    public function setFann($fann)
    {
        if (!is_resource($fann))
            throw new \Exception('supplied fann is not a resource');
        //error_log('setFann('.get_resource_type($fann).')');
        //var_dump(debug_backtrace());
        //if (is_resource($this->_fann)) $this->destroyFann(); // do not destroy, it may have a reference
        $this->_fann = $fann;
        $this->initFann();
        return true;
    }


    public function saveFann()
    {
        $fn = $this->path();
        if (!fann_save($this->getFann(), $fn))
        {
            error_log('saveFann to '.$fn.' failed');
            return false;
        }
        if (!chmod($fn, 0666))
        {
            error_log('chmod of '.$fn.' failed');
            return false;
        }
        return true;
    }



    public function delete()
    {
        // remove trainings
        FannTraining::where('strategy_id', $this->getParam('id'))->delete();
        // remove fann file
        $fn = $this-path();
        if (is_file($fn))
            if (is_writable($fn))
                unlink($fn);

        parent::delete();
    }


    public function destroyFann()
    {
        if (is_resource($this->_fann))
            return fann_destroy($this->_fann);
        return true;
    }


    public function runFann($input, $ignore_bias = false)
    {
        $output = fann_run($this->getFann(), $input);
        if (!$ignore_bias) $output[0] -= $this->getBias();
        return $output[0];
    }


    public function getBias()
    {
        if (!$this->getParam('bias_compensation'))
            return 0; // bias disabled
        if (!is_null($this->_bias))
            return $this->_bias * $this->getParam('bias_compensation');
        $this->_bias = fann_run($this->getFann(), array_fill(0, $this->getNumInput(), 0))[0];
        //error_log('bias: '.$this->_bias);
        return $this->_bias * $this->getParam('bias_compensation');
    }


    public function resetPack()
    {
        $this->_pack_iterator = 0;
        $this->nextPack(null, 'reset');
        return true;
    }


    public function nextPack($size = null, $reset = false)
    {
        static $___pack = array();

        if ($reset == 'reset')
        {
            $___pack = [];
            return true;
        }

        $candles = $this->getCandles();

        if (!$candles->size()) return null;

        $target_pack_size = $size ? $size : $this->getParam('num_samples') + $this->getParam('target_distance');
        //echo ' '.$this->_pack_iterator;
        while ($candle = $candles->byKey($this->_pack_iterator))
        {
            $this->_pack_iterator++;
            $___pack[] = $candle;
            $current_pack_size = count($___pack);
            if ($current_pack_size <  $target_pack_size) continue;
            if ($current_pack_size == $target_pack_size) return $___pack;
            if ($current_pack_size >  $target_pack_size)
            {
                array_shift($___pack);
                return $___pack;
            }
        }
    }


    public function candlesToData($name, $force = false)
    {

        if (isset($this->_data[$name]) && !$force) return true;
        $data = array();
        $images = 0;

        $this->resetPack();
        while ($pack = $this->nextPack())
        { //echo " DEBUG stop candlesToData\n"; return false;
            $input = array();
            for ($i=0; $i<$this->getParam('num_samples'); $i++)
            {
                if ($i < $this->getParam('num_samples') - 1)
                {
                    $input[] = floatval($pack[$i]->open);
                    $input[] = floatval($pack[$i]->high);
                    $input[] = floatval($pack[$i]->low);
                    $input[] = floatval($pack[$i]->close);
                }
                else
                { // we only care about the open price for the last candle in the sample
                    $input[] = floatval($pack[$i]->open);
                    $last_ohlc4 = Series::ohlc4($pack[$i]);
                }
            }
            $output = Series::ohlc4($pack[count($pack)-1]);
            //$img_data = join(',', $input).','.$output;
            //error_log($img_data);

            /*// Normalize both input and output to -1, 1
            $min = min(min($input), $output);
            $max = max(max($input), $output);
            foreach ($input as $k => $v) $input[$k] = series::normalize($v, $min, $max);
            $output = array(series::normalize($output, $min, $max));
            */

            /*// Normalize input to -0.5, 0.5, output to bandpass -1, 1
            //$io_factor = 2;
            // Normalize input to -0.1, 0.1, output to bandpass -1, 1
            $io_factor = 10;
            $min = min($input);
            $max = max($input);
            foreach ($input as $k => $v) $input[$k] = series::normalize($v, $min, $max, -1/$io_factor, 1/$io_factor);
            $output = series::normalize($output, $min, $max, -1/$io_factor, 1/$io_factor);
            if ($output > 1) $output = 1;
            else if ($output < -1) $output = -1;
            $output = array($output);
            */

            // Normalize input to -1, 1, output is delta of last input and output scaled
            $min = min($input);
            $max = max($input);
            foreach ($input as $k => $v) $input[$k] = Series::normalize($v, $min, $max);
            $delta = $output - $last_ohlc4;
            //error_log($delta);
            $output = $delta * 100 / $last_ohlc4 / $this->getParam('output_scaling');
            if ($output > 1) $output = 1;
            else if ($output < -1) $output = -1;

            $data[] = array('input'  => $input, 'output' => array($output));

            /*
            $images++;
            $img_data = join(',', $input).','.$output[0];
            if ($images > 100) {
            $images = 0;
            echo '<img src="graph.php?d='.$img_data.'&amp;t='.round($output[0], 3).'" />';
            flush();
            }
            */
        }

        //dump($data);
        $this->_data[$name] = $data;
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
                        array($this, 'createCallback'));

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
                            array($this, 'createCallback'));
        //fann_save_train($training_data, BASE_PATH.'/fann/train.dat');

        $desired_error = 0.0000001;

        /* Fixed topology */
        $epochs_between_reports = 100;

        /* Cascade */
        $max_neurons = $max_epochs / 10;
        if ($max_neurons < 1)   $max_neurons = 1;
        if ($max_neurons > 100) $max_neurons = 100;
        $neurons_between_reports = 10;

        //echo 'Training... '; flush();
        if ($this->getParam('fann_type') === 'fixed')
            $res = fann_train_on_data($this->getFann(),
                                        $training_data,
                                        $max_epochs,
                                        $epochs_between_reports,
                                        $desired_error);
        else if ($this->getParam('fann_type') === 'cascade')
            $res = fann_cascadetrain_on_data($this->getFann(),
                                        $training_data,
                                        $max_neurons,
                                        $neurons_between_reports,
                                        $desired_error);
        else throw new \Exception('Unknown fann type.');

        $this->_bias = null;

        if ($res)
        {
            //echo 'done in '.(time()-$t).'s. Connections: '.count(fann_get_connection_array($this->getFann())).
            //      ', MSE: '.fann_get_MSE($this->getFann()).'<br />';
            return true;
        }
        return false;
    }


    public function createCallback($num_data, $num_input, $num_output)
    {

        if (!$this->_callback_type) throw new \Exception('callback type not set');

        //error_log('train callback: '.$num_data.' '.$num_input.' '.$num_output.' '.$this->_callback_iterator.' '.
        //      count($this->_data[$this->_callback_type]));
        $this->_callback_iterator++;
        return is_array($this->_data[$this->_callback_type][$this->_callback_iterator-1]) ?
                  $this->_data[$this->_callback_type][$this->_callback_iterator-1] :
                  false;
    }




    /** Mean Squared Error Reciprocal */
    public function getMSER()
    {
        $mse = fann_get_MSE($this->getFann());
        //error_log('MSER: '.$mse);
        if ($mse) return 1 / $mse;
        return 0;
    }


    public function getNumSamples()
    {
        return $this->getParam('num_samples');
    }


    public function setNumSamples(int $num)
    {
        $this->setParam('num_samples', $num);
        return $this;
    }


    public function getNumInput()
    {
        // last sample has only open
        return $this->getParam('num_samples') * 4 - 3;
    }


    public function path()
    {
        return $this->getParam('path').DIRECTORY_SEPARATOR.
                $this->getParam('id').'.fann';
    }
}
