<?php

namespace GTrader;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;

abstract class Indicator extends Base implements Gene
{
    use HasOwner, HasCache, HasStatCache, Visualizable
    {
        HasOwner::kill as protected __HasOwner__kill;
        HasOwner::visualize as __HasOwner__visualize;
        Visualizable::visualize as __Visualizable__visualize;
    }

    public const ROOT_INPUT = ['open', 'high', 'low', 'close', 'volume'];

    protected $calculated = false;
    protected $refs = [];
    protected $sleepingbag = [];

    //static $stat_cache_log = 'all';

    public function __construct(array $params = [])
    {
        parent::__construct($params);

        $this->setAllowedOwners(['GTrader\\Series', 'GTrader\\Strategy']);

        if (!$this->getParam('display.y-axis')) {
            $this->setParam('display.y-axis', 'left');
        }

        //$this->cacheSetMaxSize(10);
        //static::statCacheSetMaxSize(1000);
    }


    abstract public function calculate(bool $force_rerun = false);


    protected function beforeCalculate()
    {
        Event::dispatch($this, 'indicator.beforeCalculate', []);
        //Log::debug($this->oid());
    }


    public function calculated($set = null)
    {
        if (null === $set) return $this->calculated;
        $this->calculated = $set;
        return $this;
    }


    public function init()
    {
        return $this;
    }


    public function __clone()
    {
        //dump('cloned '.$this->oid());
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


    public function kill()
    {
        $this->__HasOwner__kill();
        return $this;
    }


    public function update(array $params = [], string $suffix = '')
    {
        $before = $this->getSignature();
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
                            //dd($this->oid());
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
        $this->handleChange($before, $this->getSignature());
        return $this;
    }


    public function addRef($ind_or_sig)
    {
        //dump('addRef '.$this->oid(), $ind_or_sig);
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
            //dump($this->oid().' addRef('.$sig.')');
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


    public function getSignature(string $output = null, int $json_options = 0)
    {
        if (! $class = $this->getShortClass()) {
            Log::error('Class not found for '.$this->oid());
            return null;
        }
        if ($output) {
            if (!in_array($output, $this->getOutputs())) {
                Log::error('Invalid output '.$output.' for '.$this->getShortClass(), $this->getOutputs());
            }
        } else {
            $output = $this->getOutputs()[0];
            //Log::error('Null output requested for '.$this->getShortClass());
        }

        $params = $this->getParam('indicator', []);
        if (!is_array($params)) {
            //Log::error('Not array in '.$this->oid().' params: '.serialize($params));
            $params = (array)$params;
        }
        $out_params = [];
        foreach ($params as $key => $value) {
            $type = $this->getParam('adjustable.'.$key.'.type');
            // TODO convert to switch for readibility
            if ('int' === $type) {
                $value = intval($value);
            } elseif ('float' === $type) {
                $value = floatval($value);
            } elseif ('bool' === $type) {
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
            } elseif ('select' === $type) {
                // select needs no transformation
            }
            /*
            else { // unknown type
                Log::debug('unhandled type: '.$type);
            }
            */
            //dump($key, $value);
            $out_params[$key] = $value;
        }

        return json_encode(
            [
                'class' => $class,
                'params' => $out_params,
                'output' => $output,
            ],
            $json_options
        );
    }


    public static function decodeSignature(string $sig)
    {
        if (static::decodeCacheEnabled()
            && $cached = static::statCached('decoded: '.$sig)) {
            return $cached;
        }
        if (!strlen($sig) ||
            in_array($sig, ['open', 'high', 'low', 'close', 'volume']) ||
            config('GTrader.Indicators.available.'.$sig)) {
            return null;
        }
        //dump('decodeSignature() '.$sig);
        if (is_null($a = json_decode($sig, true)) || json_last_error()) {
            //Log::error('Could not decode sig: '.$sig
            //    .' en: '.json_last_error().' em: '.json_last_error_msg());
            return null;
        }
        $decoded = [
            'class' => Arr::get($a, 'class', ''),
            'params' => Arr::get($a, 'params', []),
            'output' => Arr::get($a, 'output', ''),
        ];
        if (static::decodeCacheEnabled()) {
            static::statCache('decoded: '.$sig, $decoded);
        }
        return $decoded;
    }


    public static function decodeCacheEnabled(bool $set = null): bool
    {
        static $enabled = true;
        if (!is_null($set)) {
            $enabled = $set;
        }
        return $enabled;
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

        if ($param_str = $this->getParamString([], $overrides, $format)) {
            $name .= ' ('.$param_str.')';
        }

        return $output ? $name.' => '.$output : $name;
    }


    public function getParamString(
        array $except_keys = [],
        array $overrides = [],
        string $format = 'long')
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
                    $format,
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
        dump('Indicator::checkAndRun() '.$this->oid().
            ' C: '.$this->getCandles()->oid().
            ' CS: '.$this->getCandles()->getStrategy()->oid());
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


    protected function handleChange(string $before, string $after)
    {
        //if ('Ema' == $this->getShortClass()) {
        //    Log::debug('Ema changed: '.$this->getParam('indicator.length'));
        //}
        if ($before !== $after) {
            Event::dispatch(
                $this,
                'indicator.change',
                [
                    'signature' => [
                        'old' => $before,
                        'new' => $after,
                    ],
                ]
            );
            $this->cleanCache();
            $this->calculated(false);
        }
        return $this;
    }


    public function nesting(int $level = 0): int
    {
        if (!$this->hasInputs()) {
            return $level + 1;
        }
        if (!$inds = $this->getOrAddInputIndicators()) {
            return $level + 1;
        }
        $levels = [];
        foreach ($inds as $ind) {
            $levels[] = $ind->nesting($level + 1);
        }
        return max($levels);
    }


    public function crossover(Gene $other, float $weight = .5): Gene
    {
        if (!$other instanceof $this) {
            Log::error('Hybrid speciation not allowed.');
            return $this;
        }
        $before = $this->getSignature();
        foreach ($this->getParam('adjustable', []) as $key => $params) {
            $this->setParam(
                'indicator.'.$key,
                $this->mixParam(
                    $this->getParam('indicator.'.$key),
                    $other->getParam('indicator.'.$key),
                    $params,
                    $weight
                )
            );
        }
        $this->handleChange($before, $this->getSignature());
        return $this;
    }


    protected function mixParam($par1, $par2, array $params, float $weight = .5)
    {
        if ($par1 === $par2
            || 0 >= $weight
            || false === Arr::get($params, 'evolvable')
        ) {
            return $par1;
        }
        if (1 <= $weight) {
            return $par2;
        }
        switch ($type = Arr::get($params, 'type')) {
            case 'string':
            case 'source':
            case 'select':
            case 'list':
                return (.5 > Rand::floatNormal(0, 1, 1, $weight)) ? $par1 : $par2;
            case 'int':
            case 'float':
            case 'bool':
                $new = Rand::floatNormal($par1, $par2, $par2, $weight);
                if ('bool' === $type) {
                    return boolval(round($new));
                }
                if ('int' === $type) {
                    $new = intval($new);
                }
                if ($min = Arr::get($params, 'min')) {
                    $new = max($new, $min);
                }
                if ($max = Arr::get($params, 'max')) {
                    $new = min($new, $max);
                }
                return $new;
            default:
                Log::error('Unknown type', $type);
                return .5 > Rand::floatNormal(0, 1, 1, $weight) ? $par1 : $par2;
        }
    }


    public function mutate(float $rate, int $max_nesting): Gene
    {
        $before = $this->getSignature();
        foreach ($this->getParam('adjustable', []) as $key => $params) {
            if (!$this->mutable($key)) {
                continue;
            }
            $this->setParam('indicator.'.$key,
                $this->mutateParam(
                    $this->getParam('indicator.'.$key),
                    $params,
                    $rate,
                    $max_nesting
                )
            );
            //if ('Ema' == $this->getShortClass() && 'length' == $key) Log::sparse('Mutate Ema '.$key.' '.$this->getParam('indicator.'.$key));
        }
        $this->handleChange($before, $this->getSignature());
        return $this;
    }


    protected function mutateParam(
        $param,
        array $params = [],
        float $rate = 0,
        int $max_nesting)
    {
        if (!$rate) {
            return $param;
        }

        if ($params['immutable'] ?? false) {
            //Log::debug('Immutable', $this->oid(), $param, $params);
            return $param;
        }

        // Weight of the current value
        $weight = 1 - $rate;

        // TODO move hardcoded factor to GUI
        if (.5 > Rand::floatNormal(0, 1, 0, $weight / 200)) {
            return $param;
        }

        //dump('Indicator::mutateParam() '.$this->getShortClass().' mutating ', $param);

        switch ($type = Arr::get($params, 'type')) {

            case 'int':
            case 'float':
            case 'bool':
                $new = Rand::floatNormal(
                    $min = Arr::get($params, 'min', 0),
                    $max = Arr::get($params, 'max', 1),
                    $param,
                    $weight
                );
                if ('bool' === $type) {
                    return boolval(round($new));
                }
                if ('int' === $type) {
                    $new = intval($new);
                }
                if ($min) {
                    $new = max($new, $min);
                }
                if ($max) {
                    $new = min($new, $max);
                }
                return $new;

            case 'string':
                return $param;

            case 'source':
            case 'select':
            case 'list':
                // if (.5 < Rand::floatNormal(0, 1, 1, $weight)) {
                //     return $param;
                // }
                $options = []; // stop complaining, Stan
                if ('source' == $type) {
                    if (!$owner = $this->getOwner()) {
                        return $param;
                    }
                    if (1 >= count($options = array_keys(
                        $owner->getAvailableSources(
                            [$this->getSignature()],
                            [],
                            ['display.visible' => true],
                            [],
                            ($max_nesting > 1) ? ($max_nesting - 1) : 1
                        )))) {
                        return $param;
                    }
                } elseif ('select' == $type) {
                    $options = array_keys(Arr::get($params, 'options', []));
                }
                if ('list' != $type) {
                    $new = Rand::pick($options);
                    //dump($new, $options);
                    return $new;
                }
                // List
                if (!is_array($param)) {
                    return [];
                }
                // make sure the indexes are sequential
                $param = array_values($param);
                // TODO are we "doubling the unlikeliness" of modifying the list?
                // And is that bad? E.g. how many tries does it take to work out
                // an optimal list for trader_cdl?
                if (.5 < Rand::floatNormal(0, 1, 1, $weight)) {
                    $param = Rand::delete($param);
                }
                if (.5 <= Rand::floatNormal(0, 1, 1, $weight)) {
                    // Add an item
                    if (!is_array($items = Arr::get($params, 'items', []))) {
                        return $param;
                    }
                    if (!count($items)) {
                        return $param;
                    }
                    if (!count($diff = array_diff($items, $param))) {
                        return $param;
                    }
                    $param[] = Rand::pick($diff);
                }
                return $param;

            default:
                Log::error('Unknown type', $type);
                return $param;
        }
    }


    public function getUserId()
    {
        if ($id = Auth::id()) {
            return $id;
        }
        return $this->getOwner()->getParam('user_id');
    }


    public function mutable($key_or_keys = null, $set = null)
    {
        if (is_null($key_or_keys)) {
            return is_array($mutable = $this->getParam('mutable', []))
                ? $mutable
                : [];
        }
        if (is_string($key_or_keys)) {
            if (!$this->getParam('adjustable.'.$key_or_keys)) {
                Log::error('unknown key', $key_or_keys);
                return false;
            }
            if (!is_null($set) && !$this->getParam('adjustable.'.$key_or_keys.'.immutable')) {
                $this->setParam('mutable.'.$key_or_keys, $set ? 1 : 0);
                return true;
            }
            return boolval($this->getParam('mutable.'.$key_or_keys, 0));
        }
        if (!is_array($key_or_keys)) {
            Log::error('need null, string or array', $key_or_keys);
            return false;
        }
        if (!is_null($set)) {
            Log::error('setting multiple is not implemented', $key_or_keys, $set);
            return false;
        }
        $updated = 0;
        foreach ($this->getParam('adjustable', []) as $key => $param) {
            if ($param['immutable'] ?? false) {
                continue;
            }
            if (isset($key_or_keys[$key])) {
                $updated++;
                $this->setParam('mutable.'.$key, $key_or_keys[$key] ? 1 : 0);
            }
        }
        return boolval($updated);
    }


    public function canBeMutable(): bool
    {
        foreach ($this->getParam('adjustable', []) as $param) {
            if (!($param['immutable'] ?? false)) {
                return true;
            }
        }
        return false;
    }


    public function mutableRatio(): float
    {
        $total = $mutable = 0;
        foreach ($this->getParam('adjustable', []) as $key => $param) {
            $total++;
            if ($this->mutable($key)) {
                $mutable++;
            }
        }
        return (0 < $total) ? $mutable / $total : 0;
    }


    protected function visAddMyNode()
    {
        //dump($this->oid().' Indicator::visAddMyNode');
        return $this->visAddNode($this, [
            'label' => $this->getShortClass(),
            'title' => $this->getDisplaySignature(),
            'color' => Util::toRGB($this->getSignature()),
            'group' => 'indicators',
        ]);
    }


    public function visualize(int $depth = 100)
    {
        $this->__Visualizable__visualize($depth);
        if ($depth--) {
            $this->__HasOwner__visualize($depth);
        }
        return ;
    }
}
