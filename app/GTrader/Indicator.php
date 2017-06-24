<?php

namespace GTrader;

use GTrader\Chart;

abstract class Indicator //implements \JsonSerializable
{
    use Skeleton, HasOwner
    {
        Skeleton::__construct as private __skeletonConstruct;
    }

    protected $calculated = false;
    protected $refs = [];
    protected $sleepingbag = [];


    public function __construct(array $params = [])
    {
        $this->__skeletonConstruct($params);

        $this->allowed_owners = ['GTrader\\Series', 'GTrader\\Strategy'];

        if (!$this->getParam('display.y_axis_pos')) {
            $this->setParam('display.y_axis_pos', 'left');
        }
    }


    public function __clone()
    {
        $this->calculated = false;
        $this->refs = [];
    }

/*
    public function __sleep()
    {
        //error_log('Indicator::__sleep()');
        $this->sleepingbag = $this->getParam('indicator');
        return ['sleepingbag', 'owner'];
    }

    public function __wakep()
    {
        //error_log('Indicator::__wakeup()');
        $this->setParam('indicator', $this->sleepingbag);
    }
*/
/*
    public function jsonSerialize()
    {
        //return get_object_vars($this);
        return [
            'class' => get_class($this),
            'params' => $this->getParam('indicator'),
        ];
    }
*/


    abstract public function calculate(bool $force_rerun = false);


    public function __wakeup()
    {
        $this->calculated = false;
        $this->refs = [];
    }


    public function addRef(string $signature) {

        $this->refs[$signature] = true;
    }

    public function refCount() {

        return count($this->refs);
    }



    protected function getSignatureObject()
    {
        //error_log('getSignatureObject() '.$this->debugObjId());
        $params = $this->getParam('indicator');

        $o = new \StdClass();

        if (!is_array($params)) {
            //error_log('getSignatureObject() not array in '.$this->debugObjId().' params: '.serialize($params));
            $params = (array)$params;
        }
        if (!count($params)) {
            //error_log('getSignatureObject() no params in '.$this->debugObjId());
            return $o;
        }
        foreach ($params as $key => $value) {
            $type = $this->getParam('adjustable.'.$key.'.type');
            /*
            if (is_object($value)) {
                echo 'Error: got object as value: ';
                var_dump($value);
                print_r(debug_backtrace());
                exit;
            }
            */
            if ('bool' === $type) {
                $value = intval($value);
            }
            else if ('source' === $type) {
                if (! $owner = $this->getOwner()) {
                    error_log('getSignatureObject() owner not found for '.$this->debugObjId());
                }
                else if (! $ind = $owner->getOrAddIndicator($value)) {
                    //error_log('getSignatureObject() could not getOrAddIndicator '.json_encode($value));
                }
                if (is_object($ind)) {
                    if ($ind->debugObjId() === $this->debugObjId()) {
                        error_log('getSignatureObject() trying to recreate myself');
                    }
                    else {
                        $value = [
                            'class' => $ind->getShortClass(),
                            'params' => $ind->getSignatureObject(),
                        ];
                    }
                }
            }
            $o->$key = $value;
        }
        return $o;
    }

    public function getSignature()
    {
        if (! $class = $this->getShortClass()) {
            error_log('getSignature() class not found for '.$this->debugObjId());
            return null;
        }
        $o = [
            'class' => $class,
            'params' => $this->getSignatureObject(),
        ];
        $sig = json_encode($o);

        //error_log('getSignature() '.$sig);

        return $sig;
    }

    protected static function decodeSignature(string $signature)
    {
        static $cache = [];

        if (isset($cache[$signature])) {
            return $cache[$signature];
        }

        $delimiter = ':::';
        $stripped = $signature;
        $output = '';
        $class = '';
        $params = [];
        if (false !== strrpos($signature, $delimiter)) {
            $chunks = explode($delimiter, $signature);
            $output = array_pop($chunks);
            $stripped = join('', $chunks);
        }
        if (!is_null($o = json_decode($stripped))) {
            $class = isset($o->class) ? $o->class : '';
            $params = isset($o->params) ? (array)$o->params : [];
            $params = ['indicator' => $params];
            $cache[$signature] = ['class' => $class, 'params' => $params, 'output' => $output];
        }
        else {
            $cache[$signature] = false;
        }
        return $cache[$signature];
    }


    public static function getClassFromSignature(string $signature)
    {
        return ($decoded = self::decodeSignature($signature)) ? $decoded['class'] : $signature;
    }

    public static function getParamsFromSignature(string $signature)
    {
        return ($decoded = self::decodeSignature($signature)) ? $decoded['params'] : [];
    }

    public static function getOutputFromSignature(string $signature)
    {
        return ($decoded = self::decodeSignature($signature)) ? $decoded['output'] : '';
    }


    public function getDisplaySignature(string $format = 'long')
    {
        $name = $this->getParam('display.name');

        if ('short' === $format) {
            return $name;
        }

        return ($param_str = $this->getParamString()) ? $name.' ('.$param_str.')' : $name;
    }


    public function getParamString(array $except_keys = [])
    {
        if (!count($params = $this->getParam('adjustable', []))) {
            return '';
        }
        $params = array_filter(
            $params,
            function($k) use ($except_keys) {
                return false === array_search($k, $except_keys);
            },
            ARRAY_FILTER_USE_KEY
        );
        $param_str = '';
        if (is_array($params)) {
            if (count($params)) {
                $delimiter = '';
                $params_if_new = ['display' => ['visible' => false]];
                foreach ($params as $key => $value) {
                    if (strlen($param_str)) {
                        $delimiter = ', ';
                    }
                    if (isset($value['type'])) {
                        if ('select' === $value['type']) {
                            if (isset($value['options'])) {
                                if ($selected = $value['options'][$this->getParam('indicator.'.$key, 0)]) {
                                    $param_str .= $delimiter.$selected;
                                    continue;
                                }
                            }
                        }
                        if ('bool' === $value['type']) {
                            $param_str .=  ($this->getParam('indicator.'.$key)) ? $delimiter.$value['name'] : '';
                            continue;
                        }
                        if ('source' === $value['type']) {
                            //error_log('getParamString() '.$this->getShortClass().': '.$key.': '.$this->getParam('indicator.'.$key));
                            if ($indicator = $this->getOwner()
                                    ->getOrAddIndicator(
                                        $this->getParam('indicator.'.$key, ''),
                                        [],
                                        $params_if_new)) {
                                $param_str .= $delimiter.$indicator->getDisplaySignature('short');
                                continue;
                            }
                        }
                    }
                    //$param_str .= $delimiter.ucfirst(explode('', $this->getParam('indicator.'.$key))[0]);
                    $param_str .= $delimiter.ucfirst($this->getParam('indicator.'.$key));
                }
            }
        }
        return $param_str;
    }


    public function getCandles()
    {
        if ($owner = $this->getOwner()) {
            return $owner->getCandles();
        }
        return null;
    }


    public function setCandles(Series &$candles)
    {
        return $this->getOwner()->setCandles($candles);
    }


    public function createDependencies()
    {
        return $this;
    }


    public function checkAndRun(bool $force_rerun = false)
    {
        if (!$force_rerun && $this->calculated) {
            //error_log($this->getSignature().' has already run');
            return $this;
        }

        $depends = $this->getParam('depends');
        if (is_array($depends)) {
            if (count($depends)) {
                foreach ($depends as $indicator) {
                    if ($indicator !== $this) {
                        $indicator->addRef($this->getSignature());
                        $indicator->checkAndRun($force_rerun);
                    }
                }
            }
        }

        $this->calculated = true;
        return $this->calculate($force_rerun);
    }


    public function getLastValue(bool $force_rerun = false)
    {
        $this->checkAndRun($force_rerun);
        $candles = $this->getCandles();
        $key = $candles->key($this->getSignature());
        if ($last = $candles->last()) {
            return $last->$key;
        }
        return 0;
    }


    public function getForm(array $params = [])
    {
        return view('Indicators/Form',
            array_merge($params, ['indicator' => $this])
        );
    }

    public function getNormalizeParams()
    {
        return [
            'type' => $this->getParam('normalize_type', 'individual'),
            'to' => $this->getParam('normalize_to', null),
            'range' => $this->getParam('range', ['min' => null, 'max' => null]),
        ];
    }

    public function hasInputs()
    {
        return false;
    }

    public function updateReferences()
    {
        $sig = $this->getSignature();

        if (!$this->hasInputs()) {
            return $this;
        }
        if (! $owner = $this->getOwner()) {
            error_log('Indicator::updateReferences() no owner');
            return $this;
        }
        foreach ($this->getInputs() as $input_sig) {
            if (! $ind = $owner->getOrAddIndicator($input_sig)) {
                //error_log('Indicator::updateReferences() coild not getOrAdd '.$input_sig);
                continue;
            }
            $ind->addRef($sig);
        }
        return $this;
    }
}
