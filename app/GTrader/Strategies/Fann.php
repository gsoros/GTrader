<?php

namespace GTrader\Strategies;

use GTrader\Strategy;
use GTrader\Series;
use GTrader\Indicator;
use GTrader\Util;

if (!extension_loaded('fann')) throw new \Exception('FANN extension not loaded');

class Fann extends Strategy
{

    protected $_fann = null;              // fann resource
    protected $_data = [];
    protected $_pack_iterator = 0;
    protected $_callback_type = false;
    protected $_callback_iterator = 0;
    protected $_fann_loaded_from_file = false;
    protected $_bias = null;


    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $num_samples = $this->getParam('num_samples');
        $this->setParam('num_input', $num_samples * 4 - 3);
        $this->setParam('num_output', 1);
    }


    public function __wakeup()
    {
        $this->create_fann();
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
error_log('Pred Ind Sig: '.$indicator->getSignature());
            $candles->addIndicator($indicator);
            $indicator = $candles->getIndicator($indicator->getSignature());
        }

        return $indicator;
    }



    public function create_fann($config_file = false)
    {

        //error_log('create_fann('.$config_file.')');
        if (is_resource($this->_fann))
            throw new \Exception('create_fann called but _fann is already a resource');
        if ($config_file)
            $this->setParam('config_file', $config_file);
        if ($config_file = $this->getParam('config_file'))
        {
            $config_path = $this->getParam('path').DIRECTORY_SEPARATOR.$config_file;
            //error_log('config file is '.$config_path);
            if (is_file($config_path))
            {
                error_log('creating fann from '.$config_path);
                $this->_fann = fann_create_from_file($config_path);
                $this->_fann_loaded_from_file = $config_file;
            }
        }
        if (!$this->_fann)
        {
            if ($this->getParam('fann_type') === 'fixed')
            {
                $params = array_merge(
                            [$this->getParam('num_layers')],
                            [$this->getParam('num_input')],
                            $this->getParam('hidden_array'),
                            [$this->getParam('num_output')]);
                //error_log('calling fann_create_standard('.join(', ', $params).')');
                $this->_fann = call_user_func_array('fann_create_standard', $params);
                //$this->_fann = call_user_func_array('fann_create_shortcut', $params);
            }
            else if ($this->getParam('fann_type') == 'cascade')
                $this->_fann = fann_create_shortcut(
                                $this->getParam('num_layers'),
                                $this->getParam('num_input'),
                                $this->getParam('num_output'));
            else throw new \Exception('Unknown fann type');
            //fann_randomize_weights($this->_fann, -0.2, 0.2);
        }
        $this->init_fann();
        return true;
    }


    public function fann_loaded_from_file()
    {
        return $this->_fann_loaded_from_file;
    }


    public function init_fann()
    {
        if (!is_resource($this->_fann)) throw new \Exception('Cannot init fann, not a resource');
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


    public function get_fann()
    {
        if (!is_resource($this->_fann)) $this->create_fann();
        return $this->_fann;
    }


    public function copy_fann()
    {
        return fann_copy($this->get_fann());
    }


    public function set_fann($fann)
    {
        if (!is_resource($fann)) throw new \Exception('supplied fann is not a resource');
        //error_log('set_fann('.get_resource_type($fann).')');
        //var_dump(debug_backtrace());
        //if (is_resource($this->_fann)) $this->destroy_fann(); // do not destroy, it may have a reference
        $this->_fann = $fann;
        $this->init_fann();
        return true;
    }


    public function save_fann($config_file = false, $update_config_file = true)
    {

        if ($config_file)
        {
            if ($update_config_file)
                $this->setParam('config_file', $config_file);
        }
        else $config_file = $this->getParam('config_file');
        if (!$config_file)
        {
            throw new \Exception('save_fann: no config file');
            return false;
        }
        $fn = $this->getParam('path').DIRECTORY_SEPARATOR.$config_file;
        if (!fann_save($this->get_fann(), $fn))
        {
            error_log('save_fann to '.$fn.' failed');
            return false;
        }
        if (!chmod($fn, 0666))
        {
            error_log('chmod of '.$fn.' failed');
            return false;
        }
        return true;
    }


    public function destroy_fann()
    {
        if (is_resource($this->_fann))
            return fann_destroy($this->_fann);
        return true;
    }


    public function run_fann($input, $ignore_bias = false)
    {
        $output = fann_run($this->get_fann(), $input);
        if (!$ignore_bias) $output[0] -= $this->get_bias();
        return $output[0];
    }


    public function get_bias()
    {
        if (!$this->getParam('bias_compensation'))
            return 0; // bias disabled
        if (!is_null($this->_bias))
            return $this->_bias * $this->getParam('bias_compensation');
        $this->_bias = fann_run($this->get_fann(), array_fill(0, $this->getParam('num_input'), 0))[0];
        //error_log('bias: '.$this->_bias);
        return $this->_bias * $this->getParam('bias_compensation');
    }


    public function reset_pack()
    {
        $this->_pack_iterator = 0;
        $this->next_pack(null, 'reset');
        return true;
    }


    public function next_pack($size = null, $reset = false)
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


    public function candles_to_data($name, $force = false)
    {

        if (isset($this->_data[$name]) && !$force) return true;
        $data = array();
        $images = 0;

        $this->reset_pack();
        while ($pack = $this->next_pack())
        { //echo " DEBUG stop candles_to_data\n"; return false;
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
            $output = $delta * 100 / $last_ohlc4 / $this->_output_scaling;
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
        $this->candles_to_data('test');
        $this->_callback_type = 'test';
        $this->_callback_iterator = 0;
        $test_data = fann_create_train_from_callback(
                        count($this->_data['test']),
                        $this->getParam('num_input'),
                        $this->getParam('num_output'),
                        array($this, 'create_callback'));

        $mse = fann_test_data($this->get_fann(), $test_data);
        //$bit_fail = fann_get_bit_fail($this->get_fann());
        //echo '<br />MSE: '.$mse.' Bit fail: '.$bit_fail.'<br />';
        return $mse;
    }


    public function train($max_epochs = 5000)
    {

        $t = time();
        $this->candles_to_data('train'); //echo " DEBUG stop that train\n"; return false;
        $this->_callback_type = 'train';
        $this->_callback_iterator = 0;
        $training_data = fann_create_train_from_callback(
                            count($this->_data['train']),
                            $this->getParam('num_input'),
                            $this->getParam('num_output'),
                            array($this, 'create_callback'));
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
            $res = fann_train_on_data($this->get_fann(),
                                        $training_data,
                                        $max_epochs,
                                        $epochs_between_reports,
                                        $desired_error);
        else if ($this->getParam('fann_type') === 'cascade')
            $res = fann_cascadetrain_on_data($this->get_fann(),
                                        $training_data,
                                        $max_neurons,
                                        $neurons_between_reports,
                                        $desired_error);
        else throw new \Exception('Unknown fann type.');

        $this->_bias = null;

        if ($res)
        {
            //echo 'done in '.(time()-$t).'s. Connections: '.count(fann_get_connection_array($this->get_fann())).
            //      ', MSE: '.fann_get_MSE($this->get_fann()).'<br />';
            return true;
        }
        return false;
    }


    public function create_callback($num_data, $num_input, $num_output)
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
    public function get_MSER()
    {
        $mse = fann_get_MSE($this->get_fann());
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
        return $this->getParam('num_input');
    }

    public function setNumInput(int $num)
    {
        $this->setParam('num_input', $num);
        return $this;
    }
}
