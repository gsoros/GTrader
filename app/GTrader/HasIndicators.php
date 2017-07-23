<?php

namespace GTrader;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

trait HasIndicators
{
    public $indicators = [];


    public function getIndicatorOwner()
    {
        return $this;
    }


    public function addIndicator(
        $indicator,
        array $params = [],
        array $params_if_new = []
    ) {
        //dump('addIndicator() i: '.json_encode($indicator).' p: '.json_encode($params).' pin: '.json_encode($params_if_new));
        $owner = $this->getIndicatorOwner();

        if (!is_object($indicator)) {
            if ($indicator) {
                $ind_str = $indicator;
                if (!($indicator = Indicator::make($indicator, ['indicator' => $params]))) {
                    error_log('addIndicator() could not make('.$ind_str.')');
                    return null;
                }
            } else {
                error_log('addIndicator() tried to make ind without class');
            }
        }
        if (!$indicator->canBeOwnedBy($owner)) {
            return null;
        }
        $indicator->setOwner($owner);
        $indicator->init();
        if ($owner->hasIndicator($sig = $indicator->getSignature())) {
            $existing = $owner->getIndicator($sig);
            //$existing->setParams($indicator->getParams());
            return $existing;
        }
        $class = $indicator->getShortClass();
        if (!\Config::get('GTrader.Indicators.available.'.$class.'.allow_multiple', false) &&
            $owner->hasIndicatorClass($class)) {
            $existing = $owner->getFirstIndicatorByClass($class);
            $existing->setParams($indicator->getParams());
            return $existing;
        }
        $indicator->setParams($params_if_new);
        $owner->indicators[] = $indicator;
        $indicator->createDependencies();
        return $indicator;
    }


    public function addIndicatorBySignature(
        string $signature,
        array $params = [],
        array $params_if_new = []
    ) {
        //dump('addIndicatorBySignature() '.$signature);
        $class = Indicator::getClassFromSignature($signature);
        $sig_params = Indicator::getParamsFromSignature($signature);
        $i = $this->addIndicator($class, array_replace_recursive($sig_params, $params), $params_if_new);

        return $i;
    }


    public function getIndicator(string $sig)
    {
        foreach ($this->getIndicators() as $existing_ind) {
            if (Indicator::signatureSame($sig, $existing_ind->getSignature())) {
                return $existing_ind;
            }
        }
        return null;
    }


    public function getOrAddIndicator(
        $signature,
        array $params = [],
        array $params_if_new = []
    ) {
        if (!is_string($signature)) {
            $signature = json_encode($signature, true);
            //error_log('HasIndicators::getOrAddIndicator() warning, converted to string: '.$signature);
        }
        if (!strlen($signature)) {
            return false;
        }
        if (in_array($signature, ['open', 'high', 'low', 'close', 'volume'])) {
            return false;
        }
        if (!($indicator = $this->getIndicator($signature))) {
            if (!($indicator = $this->addIndicatorBySignature($signature, $params, $params_if_new))) {
                return false;
            }
        }
        return $indicator;
    }


    public function getFirstIndicatorByClass(string $class)
    {
        foreach ($this->getIndicators() as $indicator) {
            if ($indicator->getShortClass() === $class) {
                return $indicator;
            }
        }
        return null;
    }


    public function hasIndicator(string $signature)
    {
        return $this->getIndicator($signature) ? true : false;
    }


    public function hasIndicatorClass(string $class, array $filters = [])
    {
        foreach ($this->getIndicators() as $indicator) {
            if ($indicator->getShortClass() === $class) {
                if (!count($filters)) {
                    return true;
                }
                foreach ($filters as $conf => $val) {
                    if ($indicator->getParam($conf) != $val) {
                        return false;
                    }
                }
                return true;
            }
        }
        return false;
    }


    public function getIndicatorLastValue(string $class, array $params = [], bool $force_rerun = false)
    {
        $indicator = Indicator::make($class, $params);
        if (!$this->hasIndicatorClass($class, $params)) {
            $this->addIndicator($indicator);
        }
        $sig = $indicator->getSignature();
        if (!($indicator = $this->getIndicator($sig))) {
            error_log('getIndicatorLastValue: '.$sig.' not found.');
            return 0;
        }
        return $indicator->getLastValue($force_rerun);
    }


    public function unsetIndicator(Indicator $indicator)
    {
        $sig = $indicator->getSignature();
        $target = null;
        foreach ($this->getIndicatorOwner()->indicators as $key => $existing) {
            if ($indicator === $existing) {
                $target = $existing;
                break;
            }
        }
        if (is_null($target)) {
            //error_log('unsetIndicator() not found: '.$sig);
            return $this;
        }
        if (0 < $target->refCount() && ['root'] !== array_merge($target->getRefs())) {
            error_log('unsetIndicator() warning: refcount is non-zero for '.$sig);
        }
        //error_log('unsetIndicator() '.$target->debugObjId());
        unset($this->getIndicatorOwner()->indicators[$key]);
        return $this;
    }


    public function unsetIndicators(string $sig)
    {
        foreach ($this->getIndicators() as $key => $existing_ind) {
            if ($sig === $existing_ind->getSignature()) {
                $this->unsetIndicator($existing_ind);
            }
        }
        return $this;
    }


    public function getIndicators()
    {
        return $this->getIndicatorOwner()->indicators;
    }


    /**
     * Get indicators, filtered, sorted
     *
     * @param  array    $filters    e.g. ['display.visible' => true]
     * @param  array    $sort       e.g. ['display.y-axis' => 'left', 'display.name']
     * @return array
     */
    public function getIndicatorsFilteredSorted(array $filters = [], array $sort = [])
    {
        $indicators = $this->getIndicators();

        if (count($filters)) {
            foreach ($indicators as $ind_key => $ind_obj) {
                foreach ($filters as $cond_key => $cond_val) {
                    if ('class' === $cond_key) {
                        if ($cond_val !== $ind_obj->getShortClass()) {
                            unset($indicators[$ind_key]);
                            break;
                        }
                        continue;
                    }
                    if ($ind_obj->getParam($cond_key) !== $cond_val) {
                        unset($indicators[$ind_key]);
                        break;
                    }
                }
            }
        }

        if (count($sort)) {
            foreach (array_reverse($sort) as $sort_key => $sort_val) {
                if (is_string($sort_key)) {
                    usort(
                        $indicators,
                        function (Indicator $ind1, Indicator $ind2) use ($sort_key, $sort_val) {
                            $val1 = $ind1->getParam($sort_key);
                            $val2 = $ind2->getParam($sort_key);
                            if ($val1 === $sort_val) {
                                return ($val2 === $sort_val) ? 0 : -1;
                            }
                            if ($val2 === $sort_val) {
                                return 1;
                            }
                            return 0;
                        }
                    );
                } else {
                    usort(
                        $indicators,
                        function (Indicator $ind1, Indicator $ind2) use ($sort_val) {
                            $val1 = $ind1->getParam($sort_val);
                            $val2 = $ind2->getParam($sort_val);
                            if (is_numeric($val1) && is_numeric($val2)) {
                                $val1 = floatval($val1);
                                $val2 = floatval($val2);
                                return $val1 === $val2 ? 0 : ($val1 > $val2 ? 1 : -1);
                            }
                            return strcmp(strval($val1), strval($val2));
                        }
                    );
                }
            }
        }
        //dump($filters, $sort, $indicators);
        return $indicators;
    }


    public function unsetAllIndicators()
    {
        $this->getIndicatorOwner()->indicators = [];
        return $this;
    }


    public function calculateIndicators()
    {
        foreach ($this->getIndicators() as $indicator) {
            //dump('HasInd::calculateIndicators() '.$indicator->debugObjId());
            $indicator->checkAndRun();
        }
        return $this;
    }


    public function dumpIndicators()
    {
        $dump = '';
        foreach ($this->getIndicators() as $indicator) {
            //$dump .= var_export($indicator->getParams(), true)."\n";
            $dump .= $indicator->getSignature()."\n";
        }
        return $dump;
    }


    public function getIndicatorsAvailable()
    {
        if ($cache_enabled = method_exists($this, 'cached')) {
            if ($available = $this->cached('indicators_available')) {
                return $available;
            }
        }
        $available = [];
        if (!is_object($owner = $this->getIndicatorOwner())) {
            return $available;
        }
        foreach (\Config::get('GTrader.Indicators.available', []) as $class => $params) {
            $exists = $owner->hasIndicatorClass($class, ['display.visible' => true]);
            if (!$exists || ($exists && true === $params['allow_multiple'])) {
                if (!$indicator = Indicator::make($class)) {
                    error_log('getIndicatorsAvailable() could not make '.$class);
                    continue;
                }
                if ($indicator->canBeOwnedBy($owner)) {
                    $available[$class] = $indicator->getParam('display.name');
                }
            }
        }
        if ($cache_enabled) {
            $this->cache('indicators_available', $available);
        }
        return $available;
    }


    protected function formatFromRequest(Request $request = null)
    {
        $format = 'long';
        if (is_object($request)) {
            if (isset($request->width)) {
                if (500 > intval($request->width)) {
                    $format = 'short';
                }
            }
        }
        return $format;
    }


    public function viewIndicatorsList(Request $request = null)
    {
        $format = $this->formatFromRequest($request);
        return view(
            'Indicators/List',
            [
                'owner' => $this,
                'indicators' => $this->getIndicatorsVisibleSorted(),
                'available' => $this->getIndicatorsAvailable(),
                'name' => $this->getParam('name'),
                'format' => $format,
            ]
        );
    }


    public function handleIndicatorFormRequest(Request $request)
    {
        $pass_params = [];
        foreach (['name', 'owner_class', 'owner_id', 'target_element'] as $val) {
            if (isset($request[$val])) {
                $pass_params[$val] = $request[$val];
            }
        }
        $sig = urldecode($request->signature);
        if (! $indicator = $this->getIndicator($sig)) {
            error_log('HasIndicators::handleIndicatorFormRequest() could not find indicator '.$sig);
        }
        return $indicator->getForm($pass_params);
    }


    public function handleIndicatorNewRequest(Request $request)
    {
        if (!$sig = urldecode($request->signature)) {
            error_log('handleIndicatorNewRequest without signature');
            return $this->viewIndicatorsList($request);
        }
        if ($indicator = $this->addIndicatorBySignature($sig)) {
            $indicator->setParam('display.visible', true);
            $indicator->addRef('root');
        }
        return $this->viewIndicatorsList($request);
    }


    public function createIndicator(string $signature)
    {
        return Indicator::make($signature);
    }


    public function handleIndicatorDeleteRequest(Request $request)
    {
        $sig = urldecode($request->signature);
        error_log('handleIndicatorDeleteRequest() '.$sig);
        $indicator = $this->getIndicator($sig);
        $this->updateReferences();
        if ($indicator->refCount()) {
            error_log('handleIndicatorDeleteRequest() has refCount, hiding');
            $indicator->setParam('display.visible', false);
        } else {
            $this->unsetIndicators($sig);
        }
        return $this->viewIndicatorsList($request);
    }


    public function handleIndicatorSaveRequest(Request $request)
    {
        //error_log('handleIndicatorSaveRequest() req: '.json_encode($request->all()));
        $sig = urldecode($request->signature);
        if (! $indicator = $this->getIndicator($sig)) {
            error_log('handleIndicatorSaveRequest() cannot find indicator '.$sig);
            return $this->viewIndicatorsList($request);
        }
        $indicator = clone $indicator;
        $sig = $indicator->getSignature();
        $jso = json_decode($request->params);
        foreach ($indicator->getParam('adjustable') as $key => $param) {
            $val = null;
            if (isset($jso->$key)) {
                $val = $jso->$key;
            }
            if ('bool' === ($type = $param['type'])) {
                $val = boolval($val);
            } elseif ('string' === $type) {
                $val = strval($val);
            } elseif ('int' === $type) {
                $val = intval($val);
            } elseif ('float' === $type) {
                $val = floatval($val);
            } elseif ('select' === $type) {
                if (isset($param['options'])) {
                    if (is_array($param['options'])) {
                        if (!array_key_exists($val, $param['options'])) {
                            $val = null;
                        }
                    }
                }
            } elseif ('list' === $type) {
                $items = [];
                if (isset($param['items'])) {
                    if (is_array($param['items'])) {
                        $items = $param['items'];
                    }
                }
                if (!is_array($val)) {
                    $val = [$val];
                }
                $val = array_intersect(array_keys($items), $val);
            } elseif ('source' === $type) {
                $val = stripslashes(urldecode($val));
                $dependency = $this->getOrAddIndicator($val);
                if (is_object($dependency)) {
                    $dependency->addRef('root');
                    $val = $dependency->getSignature(Indicator::getOutputFromSignature($val));
                }
            }
            $indicator->setParam('indicator.'.$key, $val);
        }
        $indicator->init();
        $this->unsetIndicators($sig);
        if (!$indicator = $this->addIndicator($indicator)) {
            error_log('HasIndicators::handleIndicatorSaveRequest() could not save');
            $this->viewIndicatorsList($request);
        }
        $indicator->setParam('display.visible', true);
        $indicator->addRef('root');
        if (method_exists($indicator, 'createDependencies')) {
            $indicator->createDependencies();
        }
        return $this->viewIndicatorsList($request);
    }


    public function getSourcesAvailable(
        string $except_signature = null,
        array $sources = [],
        array $filters = [],
        array $disabled = []
    ) {
        foreach ($this->getIndicatorsFilteredSorted($filters, ['display.name']) as $ind) {
            if ($ind->getParam('display.top_level')) {
                continue;
            }
            if (Indicator::signatureSame($except_signature, $ind->getSignature())) {
                continue;
            }
            if (in_array('outputs', $disabled)) {
                $sources[$ind->getSignature()] = $ind->getDisplaySignature();
                continue;
            }
            $outputs = $ind->getOutputs();
            $output_count = count($outputs);
            $disp_sig =  $ind->getDisplaySignature();
            foreach ($outputs as $output) {
                $sig = $ind->getSignature($output);
                $label = (1 < $output_count) ? $disp_sig.' => '.ucfirst($output) : $disp_sig;
                $sources[$sig] = $label;
            }
        }
        return $sources;
    }


    public function indicatorOutputDependsOn(string $signature, $target_sigs = ['open'])
    {
        if (!is_array($target_sigs)) {
            $target_sigs = [$target_sigs];
        }
        if (in_array($signature, $target_sigs)) {
            return true;
        }
        if (!($indicator = $this->getOrAddIndicator($signature))) {
            return false;
        }
        $o = Indicator::getOutputFromSignature($signature);
        return $indicator->outputDependsOn($target_sigs, $o);
    }


    public function getFirstIndicatorOutput(string $output)
    {
        if ($cache_enabled = method_exists($this, 'cached')) {
            if ($f = $this->cached('first_indicator_output_'.$output)) {
                return $f;
            }
        }
        $indicator = $class = null;
        $outputs = [];
        if (in_array($output, ['open', 'high', 'low', 'close'])) {
            $class = 'Ohlc';
        } elseif ('volume' === $output) {
            $class = 'Vol';
        } else {
            return $output;
        }
        $ret = null;
        if ($indicator = $this->getFirstIndicatorByClass($class)) {
            $outputs = $indicator->getOutputs();
        }
        if (!$indicator || !in_array($output, $outputs)) {
            if (!$indicator = $this->getOrAddIndicator($class)) {
                $ret = $output;
            }
        }
        if ($indicator) {
            $ret = in_array($output, $indicator->getOutputs()) ?
                $indicator->getSignature($output) : $output;
        }
        if ($cache_enabled) {
            $this->cache('first_indicator_output_'.$output, $ret);
        }
        //dump($this->debugObjId().' HasInd::getFirstIndicatorOutput('.$output.') : '.$ret);
        return $ret;
    }


    public function updateReferences()
    {
        foreach ($this->getIndicators() as $ind) {
            $ind->updateReferences();
        }
        return $this;
    }


    public function purgeIndicators()
    {
        $loop = 0;
        while ($loop < 100) {
            $this->updateReferences();
            $removed = 0;
            foreach ($this->getIndicators() as $ind) {
                if (!$ind->hasRefRecursive('root') ||
                    (['root'] == array_merge($ind->getRefs()) && // renumber keys
                    !$ind->getParam('display.visible'))) {
                    $this->unsetIndicator($ind);
                    $removed++;
                    //dump('purgeIndicators() removed '.$ind->getSignature().' from '.$this->debugObjId(), $ind);
                }
            }
            if (!$removed) {
                return $this;
            }
            $loop++;
        }
    }
}
