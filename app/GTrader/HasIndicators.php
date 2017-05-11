<?php

namespace GTrader;

trait HasIndicators
{
    protected $indicators = [];


    public function addIndicator($indicator, array $params = [])
    {
        if (!is_object($indicator)) {
            $indicator = Indicator::make($indicator, $params);
        }
        if (!$indicator->canBeOwnedBy($this)) {
            return $this;
        }
        if ($this->hasIndicator($indicator->getSignature())) {
            $existing = $this->getIndicator($indicator->getSignature());
            $existing->setParams($indicator->getParams());
            return $this;
        }
        $class = $indicator->getShortClass();
        if (!$indicator->getParam('available.'.$class.'.allow_multiple') &&
            $this->hasIndicatorClass($class)) {
            $existing = $this->getFirstIndicatorByClass($class);
            //error_log('Boo: '.$class.' '.$existing->getSignature());
            $existing->setParams($indicator->getParams());
            return $this;
        }
        //error_log(get_class($this).' adding indicator '.$indicator->getSignature());
        $indicator->setOwner($this);
        $this->indicators[] = $indicator;
        $indicator->createDependencies();

        return $this;
    }


    public function addIndicatorBySignature($sig)
    {
        $class = Indicator::getClassFromSignature($sig);
        $params = Indicator::getParamsFromSignature($sig);
        //error_log($class.', ['.json_encode($params).']');
        return $this->addIndicator($class, $params);
    }


    public function getIndicator(string $signature)
    {
        foreach ($this->getIndicators() as $indicator) {
            if ($indicator->getSignature() === $signature) {
                return $indicator;
            }
        }
        return null;
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
        //error_log('hasIndicatorClass('.$class.', '.serialize($filters).')');
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
        $indicator->checkAndRun($force_rerun);
        if ($last = $indicator->getCandles()->last()) {
            return $last->$sig;
        }
        return 0;
    }


    public function unsetIndicator(Indicator $indicator)
    {
        foreach ($this->indicators as $key => $existing) {
            if ($indicator === $existing) {
                unset($this->indicators[$key]);
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
        return $this->indicators;
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
        $this->indicators = [];
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
            $dump .= var_export($indicator->getParams(), true)."\n";
        }
        return $dump;
    }
}
