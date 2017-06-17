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
        $owner = $this->getIndicatorOwner();

        if (!is_object($indicator)) {
            if (!($indicator = Indicator::make($indicator, $params))) {
                error_log('addIndicator() could not make() '.$indicator);
                return false;
            }
        }
        if (!$indicator->canBeOwnedBy($owner)) {
            return false;
        }
        if ($owner->hasIndicator($sig = $indicator->getSignature())) {
            $existing = $owner->getIndicator($sig);
            $existing->setParam('indicator', $indicator->getParam('indicator'));
            return $existing;
        }
        $class = $indicator->getShortClass();
        if (!$indicator->getParam('available.'.$class.'.allow_multiple', false) &&
            $owner->hasIndicatorClass($class)) {
            $existing = $owner->getFirstIndicatorByClass($class);
            $existing->setParams($indicator->getParams());
            return $existing;
        }
        $indicator->setParams($params_if_new);
        $indicator->setOwner($owner);
        $owner->indicators[] = $indicator;
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
        return $this->addIndicator($class, array_replace_recursive($sig_params, $params, $params_if_new));
    }


    public function getIndicator(string $signature)
    {
        foreach ($this->getIndicators() as $indicator) {
            if (($ind_sig = $indicator->getSignature()) === $signature) {
                return $indicator;
            }
            if (1 < count($outputs = $indicator->getParam('outputs', []))) {
                foreach ($outputs as $output) {
                    if ($ind_sig.'_'.$output === $signature) {
                        return $indicator;
                    }
                }
            }
        }
        return null;
    }


    public function getOrAddIndicator(
        string $signature,
        array $params = [],
        array $params_if_new = [])
    {
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
        foreach ($this->getIndicatorOwner()->indicators as $key => $existing) {
            if ($indicator === $existing) {
                unset($this->getIndicatorOwner()->indicators[$key]);
            }
        }
        return $this;
    }


    public function unsetIndicators(string $signature)
    {
        foreach ($this->getIndicators() as $indicator) {
            if ($indicator->getSignature() === $signature) {
                $this->unsetIndicator($indicator);
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
        foreach ($this->getIndicators() as &$indicator) {
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
        $indicator = Indicator::make();
        $config = $indicator->getParam('available');
        foreach ($config as $class => $params) {
            $exists = $owner->hasIndicatorClass($class, ['display.visible' => true]);
            if (!$exists || ($exists && true === $params['allow_multiple'])) {
                $indicator = Indicator::make($class);
                if ($indicator->canBeOwnedBy($owner)) {
                    $available[$class] = $indicator->getParam('display.name');
                }
            }
        }
        //error_log(serialize($available));
        return $available;
    }


    public function handleIndicatorFormRequest(Request $request)
    {
        $pass_params = [];
        foreach (['name', 'owner_class', 'owner_id', 'target_element'] as $val) {
            if (isset($request[$val])) {
                $pass_params[$val] = $request[$val];
            }
        }
        $indicator = $this->getIndicator($request->signature);
        return $indicator->getForm(
            array_merge(['bases' => $this->getBasesAvailable($request->signature)], $pass_params)
        );
    }


    public function handleIndicatorNewRequest(Request $request)
    {
        if (!$request->signature) {
            error_log('handleIndicatorNewRequest without signature');
            return $this->viewIndicatorsList();
        }

        $indicator = $this->createIndicator($request->signature);
        if ($this->hasIndicator($indicator->getSignature())) {
            $indicator = $this->getIndicator($indicator->getSignature());
            $indicator->setParam('display.visible', true);
        } else {
            $this->addIndicator($indicator);
        }
        return $this->viewIndicatorsList();
    }


    public function createIndicator(string $signature)
    {
        return Indicator::make($signature);
    }


    public function handleIndicatorDeleteRequest(Request $request)
    {
        $indicator = $this->getIndicator($request->signature);
        $this->unsetIndicators($indicator->getSignature());
        return $this->viewIndicatorsList();
    }


    public function handleIndicatorSaveRequest(Request $request)
    {
        if ($indicator = $this->getIndicator($request->signature)) {
            $jso = json_decode($request->params);
            foreach ($indicator->getParam('indicator') as $param => $val) {
                if (isset($jso->$param)) {
                    $indicator->setParam('indicator.'.$param, $jso->$param);
                    if ($param === 'base') {
                        $indicator->setParam('depends', []);
                        $dependency = $this->getIndicator($jso->$param);
                        if (is_object($dependency)) {
                            $indicator->setParam('depends', [$dependency]);
                        }
                    }
                }
            }
            $this->unsetIndicators($indicator->getSignature());
            $indicator->setParam('display.visible', true);
            $this->addIndicator($indicator);
        }
        return $this->viewIndicatorsList();
    }


    public function getBasesAvailable(string $except_signature = null, array $bases = null)
    {
        if (!is_array($bases)) {
            $bases = [
                'open' => 'Open',
                'high' => 'High',
                'low' => 'Low',
                'close' => 'Close',
                'volume' => 'Volume'
            ];
        }
        foreach ($this->getIndicatorsFilteredSorted([], ['display.name']) as $ind) {
            if (!$ind->getParam('display.top_level') &&
                $except_signature != $ind->getSignature()) {
                $bases[$ind->getSignature()] = $ind->getDisplaySignature();
            }
        }
        return $bases;
    }


    public function indicatorIsBasedOn(string $signature, string $target_base = 'open')
    {
        if ($signature === $target_base) {
            return true;
        }
        $params = ['display' => ['visible' => false]];
        if (!($indicator = $this->getOrAddIndicator($signature, [], $params))) {
            return false;
        }
        if (!$indicator->hasBase()) {
            return false;
        }
        if ($indicator->basedOn($target_base)) {
            return true;
        }

        return false;
    }
}
