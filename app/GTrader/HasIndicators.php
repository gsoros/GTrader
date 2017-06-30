<?php

namespace GTrader;

use Illuminate\Http\Request;

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
        array $params_if_new = [])
    {
        //dump('addIndicator()', $indicator, $params, $params_if_new);
        $owner = $this->getIndicatorOwner();

        if (!is_object($indicator)) {
            $ind_str = $indicator;
            if ($indicator) {
                if (!($indicator = Indicator::make($indicator, $params))) {
                    error_log('addIndicator() could not make('.$ind_str.')');
                    return false;
                }
            }
            else {
                error_log('addIndicator() tried to make ind without class');
            }
        }
        if (!$indicator->canBeOwnedBy($owner)) {
            return false;
        }
        $indicator->setOwner($owner);
        if ($owner->hasIndicator($sig = $indicator->getSignature())) {
            $existing = $owner->getIndicator($sig);
            $existing->setParams($indicator->getParams());
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
        $owner->indicators[$sig] = $indicator;
        $indicator->createDependencies();
        return $indicator;
    }


    public function addIndicatorBySignature(
        string $signature,
        array $params = [],
        array $params_if_new = [])
    {
        $class = Indicator::getClassFromSignature($signature);
        $sig_params = Indicator::getParamsFromSignature($signature);
        $i = $this->addIndicator($class, array_replace_recursive($sig_params, $params), $params_if_new);

        return $i;
    }


    public function getIndicator(string $signature)
    {
        //error_log('getIndicator() '.$signature.' inds: '.json_encode($this->getIndicators()));
        foreach ($this->getIndicators() as $existing_sig => $indicator) {
            //error_log('getIndicator() comparing '.$signature.' to '.$existing_sig);
            if ($existing_sig === $signature) {
                //error_log('getIndicator() bingo');
                return $indicator;
            }
            if (1 < count($outputs = $indicator->getOutputs())) {
                foreach ($outputs as $output) {
                    if ($existing_sig.':::'.$output === $signature) {
                        return $indicator;
                    }
                }
            }
        }
        return null;
    }


    public function getOrAddIndicator(
        $signature,
        array $params = [],
        array $params_if_new = [])
    {
        if (!is_string($signature)) {
            $signature = json_encode($signature);
            //error_log('HasIndicators::getOrAddIndicator() warning, converted to string: '.$signature);
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
        if (!isset($this->getIndicatorOwner()->indicators[$sig])) {
            error_log('unsetIndicator() but not set: '.$sig);
            return $this;
        }
        if (0 < $indicator->refCount()) {
            error_log('unsetIndicator() warning: refcount is non-zero');
        }
        //error_log('unsetIndicator() '.$indicator->debugObjId());
        unset($this->getIndicatorOwner()->indicators[$sig]);
        return $this;
    }


    public function unsetIndicators(string $signature)
    {
        foreach ($this->getIndicators() as $existing_sig => $existing_ind) {
            //error_log($existing_sig.' vs '.$signature);
            if ($existing_sig === $signature) {
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
     * @param  array    $sort       e.g. ['display.y_axis_pos' => 'left', 'display.name']
     * @return array
     */
    public function getIndicatorsFilteredSorted(array $filters = [], array $sort = [])
    {
        $indicators = $this->getIndicators();

        if (count($filters)) {
            foreach ($indicators as $ind_key => $ind_obj) {
                foreach ($filters as $cond_key => $cond_val) {
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
                            //error_log("Comparing $val1 and $val2 to $sort_val");
                            if ($val1 === $sort_val) {
                                if ($val2 === $sort_val) {
                                    return 0;
                                } else {
                                    return -1;
                                }
                            } elseif ($val2 === $sort_val) {
                                return 1;
                            }
                            return 0;
                        }
                    );
                } else {
                    usort(
                        $indicators,
                        function (Indicator $ind1, Indicator $ind2) use ($sort_key) {
                            return strcmp(
                                $ind1->getParam($sort_key),
                                $ind2->getParam($sort_key)
                            );
                        }
                    );
                }
            }
        }
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
        //error_log(serialize($available));
        return $available;
    }


    protected function formatFromRequest(Request $request = null)
    {
        $format = 'long';
        if (is_object($request)) {
            if (isset($request->width)) {
                if (400 > intval($request->width)) {
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
            'Indicators/List', [
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
        return $indicator->getForm(
            array_merge(['sources' => $this->getSourcesAvailable(urldecode($request->signature))], $pass_params)
        );
    }


    public function handleIndicatorNewRequest(Request $request)
    {
        if (!$sig = urldecode($request->signature)) {
            error_log('handleIndicatorNewRequest without signature');
            return $this->viewIndicatorsList($request);
        }
        if ($indicator = $this->addIndicatorBySignature($sig)) {
            $indicator->setParam('display.visible', true);
            $indicator->addRef($this);
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
        }
        else {
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
        $jso = json_decode($request->params);
        foreach ($indicator->getParam('adjustable') as $key => $param) {
            $val = null;
            if (isset($jso->$key)) {
               $val = $jso->$key;
            }
            if ('bool' === ($type = $param['type'])) {
                $val = boolval($val);
            }
            else if ('string' === $type) {
                $val = strval($val);
            }
            else if ('int' === $type) {
                $val = intval($val);
            }
            else if ('float' === $type) {
                $val = floatval($val);
            }
            else if ('select' === $type) {
                if (isset($param['options'])) {
                    if (is_array($param['options'])) {
                        if (!array_key_exists($val, $param['options'])) {
                            $val = null;
                        }
                    }
                }
            }
            else if ('source' === $type) {
                $val = urldecode($val);
                // TODO is this still needed? /////////////////////////
                $indicator->setParam('depends', []);
                $dependency = $this->getOrAddIndicator($val);
                if (is_object($dependency)) {
                    $indicator->setParam('depends', [$dependency]);
                }
                ///////////////////////////////////////////////////////

            }
            $indicator->setParam('indicator.'.$key, $val);
        }
        if (method_exists($indicator, 'init')) {
            $indicator->init(true);
        }
        $this->unsetIndicators($sig);
        $indicator->setParam('display.visible', true);
        $this->addIndicator($indicator);
        return $this->viewIndicatorsList($request);
    }


    public function getSourcesAvailable(string $except_signature = null, array $sources = null)
    {
        if (!is_array($sources)) {
            $sources = [
                'open' => 'Open',
                'high' => 'High',
                'low' => 'Low',
                'close' => 'Close',
                'volume' => 'Volume'
            ];
        }
        foreach ($this->getIndicatorsFilteredSorted([], ['display.name']) as $ind) {
            if ($ind->getParam('display.top_level')) {
                continue;
            }
            if ($except_signature === ($sig = $ind->getSignature())) {
                continue;
            }
            if (1 >= count($outputs = $ind->getOutputs())) {
                $sources[$sig] = $ind->getDisplaySignature();
                continue;
            }
            foreach ($outputs as $output) {
                $sources[$sig.':::'.$output] = $ind->getDisplaySignature().' '.$output;
            }
        }
        return $sources;
    }


    public function indicatorHasInput(string $signature, $target_sigs = ['open'])
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
        if (!method_exists($indicator, 'hasInputs')) {
            return false;
        }
        if (!$indicator->hasInputs()) {
            return false;
        }
        return $indicator->inputFrom($target_sigs);
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
        $this->updateReferences();
        foreach ($this->getIndicators() as $ind) {
            //error_log('purgeIndicators() checking '.$ind->getSignature().' refs: '.$ind->refCount());
            if (!$ind->refCount() && !$ind->getParam('display.visible')) {
                //error_log('purgeIndicators() removing '.$ind->debugObjId());
                $this->getIndicatorOwner()->unsetIndicator($ind);
            }
        }
    }
}
