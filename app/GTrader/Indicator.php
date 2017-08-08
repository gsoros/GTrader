<?php

namespace GTrader;

use Illuminate\Support\Arr;
use GTrader\Series;
use GTrader\Log;

abstract class Indicator //implements \JsonSerializable
{
    use Skeleton, HasOwner, HasCache
    {
        Skeleton::__construct as private __skeletonConstruct;
    }

    protected $calculated = false;
    protected $refs = [];
    protected $sleepingbag = [];


    public function __construct(array $params = [])
    {
        $this->__skeletonConstruct($params);

        $this->setAllowedOwners(['GTrader\\Series', 'GTrader\\Strategy']);

        if (!$this->getParam('display.y-axis')) {
            $this->setParam('display.y-axis', 'left');
        }
    }


    abstract public function calculate(bool $force_rerun = false);


    public function init()
    {
        return $this;
    }


    public function __clone()
    {
        $this->calculated = false;
        $this->refs = [];
    }


    /*
        public function __sleep()
        {
            //dump('Indicator::__sleep()', $this);
            $this->sleepingbag = $this->getParam('indicator');
            return ['sleepingbag', 'owner', 'refs'];
            //return [];
        }

        public function __wakeup()
        {
            //dump('Indicator::__wakeup()', $this);

            self::loadConfRecursive(get_class($this));
            $this->setParam('indicator', $this->sleepingbag);
            $this->calculated = false;
        }
    */
    public function __wakeup()
    {
        $this->calculated = false;
    }
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


    public function update(array $params = [], string $suffix = '')
    {
        foreach ($this->getParam('adjustable', []) as $key => $param) {
            $val = null;
            if (isset($params[$key.$suffix])) {
                $val = $params[$key.$suffix];
            }
            switch ($type = Arr::get($param, 'type')) {

                case 'bool':
                    $val = boolval($val);
                    break;
                case 'string':
                    $val = strval($val);
                    break;
                case 'int':
                    $val = intval($val);
                    break;
                case 'float':
                    $val = floatval($val);
                    break;
                case 'select':
                    $options = Arr::get($param, 'options');
                    if (is_array($options)) {
                        if (!array_key_exists($val, $options)) {
                            $val = null;
                        }
                    }
                    break;
                case 'list':
                    $tmpval = $val;
                    $val = null;
                    $available = Arr::get($param, 'items');
                    if (is_array($available)) {
                        if (!is_array($tmpval)) {
                            $tmpval = [$tmpval];
                        }
                        $val = array_values(array_intersect(array_keys($available), $tmpval));
                    }
                    break;
                case 'source':
                    if (!isset($owner)) {
                        if (!$owner = $this->getOwner()) {
                            Log::error('Could not get owner', $this);
                            return $this;
                        }
                    }
                    $val = stripslashes(urldecode($val));
                    $dependency = $owner->getOrAddIndicator($val);
                    if (is_object($dependency)) {
                        //$dependency->addRef('root');
                        $val = $dependency->getSignature(Indicator::getOutputFromSignature($val));
                    }
                    break;
                default:
                    Log::error('unknown param type: ', $type);

            }
            $this->setParam('indicator.'.$key, $val);
        }
        return $this;
    }


    public function addRef($ind_or_sig)
    {
        //dump('addRef '.$this->debugObjId(), $ind_or_sig);
        $sig = null;
        if (is_object($ind_or_sig)) {
            if (method_exists($ind_or_sig, 'getSignature')) {
                $sig = $ind_or_sig->getSignature();
            } elseif (method_exists($ind_or_sig, 'getShortClass')) {
                $sig = $ind_or_sig->getShortClass();
            } else {
                $sig = get_class($ind_or_sig);
            }
        }
        if (is_null($sig)) {
            $sig = strval($ind_or_sig);
        }
        if (!in_array($sig, $this->refs)) {
            //dump($this->debugObjId().' addRef('.$sig.')');
            $this->refs[] = $sig;
        }
        return $this;
    }


    public function delRef(string $sig)
    {
        foreach ($this->getRefs() as $k => $v) {
            if ($sig === $v) {
                unset($this->refs[$k]);
            }
        }
        return $this;
    }


    public function refCount()
    {
        return count($this->getRefs());
    }


    public function getRefs()
    {
        return $this->refs;
    }


    public function cleanRefs(array $except = [])
    {
        if (!count($except)) {
            $this->refs = [];
            return $this;
        }
        $new_refs = [];
        foreach ($this->refs as $ref) {
            if (in_array($ref, $except)) {
                $new_refs[] = $ref;
            }
        }
        $this->refs = $new_refs;
        return $this;
    }


    public function hasRefRecursive(string $sig)
    {
        if (in_array($sig, $this->getRefs())) {
            return true;
        }
        if (!$owner = $this->getOwner()) {
            Log::error('Could not getOwner() for '.$this->getShortClass());
            return false;
        }
        foreach ($this->getRefs() as $ref) {
            if ($i = $owner->getIndicator($ref)) {
                if ($i->hasRefRecursive($sig)) {
                    return true;
                }
            }
        }
        return false;
    }


    public function getSignature(string $output = null)
    {
        if (! $class = $this->getShortClass()) {
            Log::error('Class not found for '.$this->debugObjId());
            return null;
        }
        if ($output) {
            if (!in_array($output, $this->getOutputs())) {
                Log::error('Invalid output '.$output.' for '.$this->getShortClass());
            }
        } else {
            $output = $this->getOutputs()[0];
            //Log::error('Null output requested for '.$this->getShortClass());
        }

        $params = $this->getParam('indicator', []);
        if (!is_array($params)) {
            //Log::error('Not array in '.$this->debugObjId().' params: '.serialize($params));
            $params = (array)$params;
        }
        $out_params = [];
        foreach ($params as $key => $value) {
            $type = $this->getParam('adjustable.'.$key.'.type');
            if ('bool' === $type) {
                $value = intval($value);
            } elseif ('string' === $type) {
                $value = strval($value);
            } elseif ('source' === $type) {
                if (!in_array($value, ['time', 'open', 'high', 'low', 'close', 'volume'])) {
                    if (!is_array($value)) {
                        $value = self::decodeSignature($value);
                    }
                }
            } elseif ('list' === $type) {
                if (is_array($value)) {
                    // convert assoc arr to indexed
                    $value = array_values($value);
                }
            }
            /*
            else { // unknown type
                if ($temp_value = self::decodeSignature($value)) {
                    if (Arr::get($temp_value, 'class') &&
                        Arr::get($temp_value, 'params') &&
                        Arr::get($temp_value, 'output')) {
                        $value = $temp_value;
                    }
                }
            }
            */
            //dump($key, $value);
            $out_params[$key] = $value;
        }
        $a = [
            'class' => $class,
            'params' => $out_params,
            'output' => $output,
        ];
        $sig = json_encode($a);

        //Log::info('getSignature() '.$sig);

        return $sig;
    }


    public static function decodeSignature(string $sig)
    {
        static $cache = [];

        if (isset($cache[$sig])) {
            //Log::info('Cache hit for '.$sig);
            return $cache[$sig];
        }
        if (!strlen($sig) ||
            in_array($sig, ['open', 'high', 'low', 'close', 'volume']) ||
            config('GTrader.Indicators.available.'.$sig)) {
            $cache[$sig] = false;
            return false;
        }
        //dump('decodeSignature() '.$sig);
        if (is_null($a = json_decode($sig, true)) || json_last_error()) {
            $cache[$sig] = false;
            //Log::error('Could not decode sig: '.$sig
            //    .' en: '.json_last_error().' em: '.json_last_error_msg());
            return false;
        }
        $cache[$sig] = [
            'class' => Arr::get($a, 'class', ''),
            'params' => Arr::get($a, 'params', []),
            'output' => Arr::get($a, 'output', ''),
        ];
        //Log::info('Uncached: '.json_encode($cache[$sig]));
        return $cache[$sig];
    }


    public static function getClassFromSignature(string $signature)
    {
        if (in_array($signature, ['time', 'open', 'high', 'low', 'close', 'volume'])) {
            return $signature;
        }
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


    public function getDisplaySignature(
      string $format = 'long',
      string $output = null,
      array $overrides = [])
    {
        $name = $this->getParam('display.name', '');

        if ('short' === $format) {
            return $name;
        }

        if ($param_str = $this->getParamString([], $overrides)) {
            $name .= ' ('.$param_str.')';
        }

        return $output ? $name.' => '.$output : $name;
    }


    public function getParamString(array $except_keys = [], array $overrides = [])
    {
        if (!count($params = $this->getParam('adjustable', []))) {
            return '';
        }
        $params = array_filter(
            $params,
            function ($k) use ($except_keys) {
                return false === array_search($k, $except_keys);
            },
            ARRAY_FILTER_USE_KEY
        );
        $param_str = '';
        if (!is_array($params)) {
            return $param_str;
        }
        if (!count($params)) {
            return $param_str;
        }
        $delimiter = '';
        foreach ($params as $key => $value) {
            if (strlen($param_str)) {
                $delimiter = ', ';
            }
            if (isset($overrides[$key])) {
                $param_str .= $delimiter.strval($overrides[$key]);
                continue;
            }
            if (!isset($value['type'])) {
                continue;
            }
            if ('select' === $value['type']) {
                if (!isset($value['options'])) {
                    continue;
                }
                if ($selected = Arr::get($value['options'], $this->getParam('indicator.'.$key, 0))) {
                    $param_str .= $delimiter.$selected;
                    continue;
                }
            }
            if ('bool' === $value['type']) {
                $param_str .=  ($this->getParam('indicator.'.$key)) ? $delimiter.$value['name'] : '';
                continue;
            }
            if ('source' === $value['type']) {
                $sig = $this->getParam('indicator.'.$key, '');
                if (!$indicator = $this->getOwner()->getOrAddIndicator($sig)) {
                    continue;
                }
                $output = is_array($sig) ?
                    Arr::get($sig, 'output') :
                    Indicator::getOutputFromSignature($sig);
                $param_str .= $delimiter.$indicator->getDisplaySignature(
                    'short',
                    $output
                );
                continue;
            }
            $param = $this->getParam('indicator.'.$key);
            if (is_array($param)) {
                Log::error('Indicator.'.$key.' is an array');
                return '';
                //dd($this);
            }
            //$param_str .= $delimiter.ucfirst(explode('', $this->getParam('indicator.'.$key))[0]);
            $param_str .= $delimiter.ucfirst($param);
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
            return $this;
        }
        /*
        dump('Indicator::checkAndRun() '.$this->debugObjId().
            ' C: '.$this->getCandles()->debugObjId().
            ' CS: '.$this->getCandles()->getStrategy()->debugObjId());
        */
        $this->calculated = true;
        $ret = $this->calculate($force_rerun);
        //if ('Ht' == $this->getShortClass()) dump($this);
        return $ret;
    }


    public function getLastValue(bool $force_rerun = false)
    {
        $this->checkAndRun($force_rerun);
        $candles = $this->getCandles();
        $key = $candles->key($this->getSignature());
        if ($last = $candles->last()) {
            return $last->$key ?? 0;
        }
        return 0;
    }


    public function getForm(array $params = [])
    {
        return view(
            'Indicators/Form',
            array_merge($params, ['indicator' => $this])
        );
    }


    public function getNormalizeParams()
    {
        return [
            'mode' => $this->getParam('normalize.mode', 'individual'),
            'to' => $this->getParam('normalize.to', null),
            'range' => $this->getParam('normalize.range', ['min' => null, 'max' => null]),
        ];
    }


    public function hasInputs()
    {
        return false;
    }


    public function updateReferences(string $update_id = null)
    {
        if (!$update_id) {
            $update_id = uniqid();
        } elseif ($update_id === $this->cached('last_references_update_id')) {
            return $this;
        }
        if (! $owner = $this->getOwner()) {
            Log::error('No owner');
            return $this;
        }
        /*** // References are reset by HasIndicators::updateReferences
        foreach ($this->getRefs() as $ref) {
            if ('root' === $ref) {
                continue;
            }
            if (!$owner->hasIndicator($ref)) {
                $this->delRef($ref);
            }
        }
        */
        if (!$this->hasInputs()) {
            return $this;
        }
        foreach ($this->getInputs() as $input_sig) {
            if (!strlen($input_sig)) {
                continue;
            }
            if (! $ind = $owner->getOrAddIndicator($input_sig)) {
                //Log::error('Could not getOrAdd '.$input_sig);
                continue;
            }
            $ind->addRef($this);
            $ind->updateReferences($update_id);
        }
        $this->cache('last_references_update_id', $update_id);
        return $this;
    }


    public function setAutoYAxis()
    {
        if (!$this->getParam('display.auto-y-axis')) {
            return false;
        }
        if (!$this->hasInputs()) {
            return $this;
        }
        $inputs = $this->getInputs();
        if (in_array('volume', $inputs)) {
            $this->setParam('display.y-axis', 'right');
        } elseif (!$this->inputFromIndicator() &&
            count(array_intersect(['open', 'high', 'low', 'close'], $inputs))) {
            $this->setParam('display.y-axis', 'left');
            return $this;
        }
        if (! $inds = $this->getOrAddInputIndicators()) {
            return $this;
        }
        $count_left = 0;
        foreach ($inds as $ind) {
            if ('left' === $ind->getParam('display.y-axis')) {
                $count_left++;
            }
        }
        $this->setParam('display.y-axis', ($count_left === count($inds)) ? 'left' : 'right');
        return $this;
    }


    public static function signatureSame(string $sig_a = null, string $sig_b = null)
    {
        if (is_null($sig_a) || is_null($sig_b)) {
            return $sig_a == $sig_b;
        }
        if (($ca = self::getClassFromSignature($sig_a))
            !== ($cb = self::getClassFromSignature($sig_b))) {
            //Log::debug('signatureSame() '.$ca.' != '.$cb);
            return false;
        }
        if (($pa = self::getParamsFromSignature($sig_a))
            !== ($pb = self::getParamsFromSignature($sig_b))) {
            //Log::error('signatureSame() '.json_encode($pa).' != '.json_encode($pb));
            return false;
        }

        return true;
    }


    public function outputDependsOn(array $sigs = [], string $output = null)
    {
        if (!method_exists($this, 'getInputs')) {
            return false;
        }
        if (count(array_intersect($inputs = $this->getInputs(), $sigs))) {
            return true;
        }
        if (!$owner = $this->getOwner()) {
            return false;
        }
        foreach ($inputs as $input) {
            $o = self::getOutputFromSignature($input);
            if ($i = $owner->getOrAddIndicator($input)) {
                if ($i->outputDependsOn($sigs, $o)) {
                    return true;
                }
            }
        }
        return false;
    }


    public function getOutputs()
    {
        return $this->getParam('outputs', ['default']);
    }


    public function getOutputArray(
        string $index_type = 'sequential',
        bool $respect_padding = false,
        int $density_cutoff = null
    ) {
        if (!$candles = $this->getCandles()) {
            Log::error('Could not get candles');
            return [];
        }
        $this->checkAndRun();
        $r = null;
        foreach ($this->getOutputs() as $output) {
            $arr = $candles->extract(
                $this->getSignature($output),
                $index_type,
                $respect_padding,
                $density_cutoff
            );
            if (!$output || is_null($r)) {
                $r = array_map(function ($v) {
                    return [$v];
                }, $arr);
                if (!$output) {
                    return $r;
                }
                continue;
            }
            array_walk($r, function (&$v1, $k) use ($arr) {
                $v2 = Arr::get($arr, $k);
                if (is_array($v1)) {
                    $v1[] = $v2;
                    return $v1;
                }
                return [$v1, $v2];
            }, $r);
        }
        return $r;
    }


    public function min(array $values)
    {
        $min = null;
        array_walk($values, function ($v) use (&$min) {
            if (is_null($min)) {
                $min = min($v);
                return;
            }
            if (is_null($v)) {
                return;
            }
            $min = min($min, min($v));
        });
        //dump('Min '.$this->getShortClass().': '.$min);
        return $min;
    }


    public function max(array $values)
    {
        $max = null;
        array_walk($values, function ($v) use (&$max) {
            if (is_null($max)) {
                $max = max($v);
                return;
            }
            if (is_null($v)) {
                return;
            }
            $max = max($max, max($v));
        });
        //dump('Max '.$this->getShortClass().': '.$max);
        return $max;
    }


    public function visible(bool $set = null)
    {
        if (is_null($set)) {
            return $this->getParam('display.visible');
        }
        $this->setParam('display.visible', $set);
        return $this;
    }
}
