<?php

namespace GTrader;

trait HasIndicators
{
    protected $_indicators = [];


    public function addIndicator($indicator, array $params = [])
    {
        if (!is_object($indicator))
            $indicator = Indicator::make($indicator, $params);

        if (!$indicator->canBeOwnedBy($this))
            return $this;

        if ($this->hasIndicator($indicator->getSignature()))
        {
            //error_log('addIndicator() we already have it, updating params');
            $existing = $this->getIndicator($indicator->getSignature());
            $existing->setParams($indicator->getParams());
            return $this;
        }
        $class = $indicator->getShortClass();
        if (!$indicator->getParam('available.'.$class.'.allow_multiple') &&
                $this->hasIndicatorClass($class))
        {
            $existing = $this->getFirstIndicatorByClass($class);
            $existing->setParams($indicator->getParams());
            return $this;
        }

        $indicator->setOwner($this);
        $this->_indicators[] = $indicator;
        $indicator->createDependencies();

        return $this;
    }


    public function getIndicator(string $signature)
    {
        foreach ($this->getIndicators() as $indicator)
            if ($indicator->getSignature() === $signature)
                return $indicator;
        return null;
    }


    public function getFirstIndicatorByClass(string $class)
    {
        foreach ($this->getIndicators() as $indicator)
            if ($indicator->getShortClass() === $class)
                return $indicator;
        return null;
    }


    public function hasIndicator(string $signature)
    {
        return $this->getIndicator($signature) ? true : false;
    }


    public function hasIndicatorClass(string $class, array $filters = [])
    {
        //error_log('hasIndicatorClass('.$class.', '.serialize($filters).')');
        foreach ($this->getIndicators() as $indicator)
            if ($indicator->getShortClass() === $class)
                if ($num_filters = count($filters))
                {
                    $num_matched = 0;
                    foreach ($filters as $conf => $val)
                        if ($indicator->getParam($conf) === $val)
                            $num_matched++;
                    if ($num_matched === $num_filters)
                        return true;
                }
                else return true;
    }



    public function unsetIndicator(Indicator $indicator)
    {
        foreach ($this->_indicators as $key => $existing)
            if ($indicator === $existing)
                unset($this->_indicators[$key]);
        return $this;
    }


    public function unsetIndicators(string $signature)
    {
        foreach ($this->getIndicators() as $indicator)
            if ($indicator->getSignature() === $signature)
                $this->unsetIndicator($indicator);
        return $this;
    }


    public function getIndicators()
    {
        return $this->_indicators;
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

        if (count($filters))
            foreach ($indicators as $ind_key => $ind_obj)
                foreach ($filters as $cond_key => $cond_val)
                    if ($ind_obj->getParam($cond_key) !== $cond_val)
                    {
                        unset($indicators[$ind_key]);
                        break;
                    }

        if (count($sort))
        {
            foreach (array_reverse($sort) as $sort_key => $sort_val)
                if (is_string($sort_key))
                    usort($indicators,
                        function (Indicator $ind1, Indicator $ind2) use ($sort_key, $sort_val) {
                            $val1 = $ind1->getParam($sort_key);
                            $val2 = $ind2->getParam($sort_key);
                            //error_log("Comparing $val1 and $val2 to $sort_val");
                            if ($val1 === $sort_val)
                                if ($val2 === $sort_val) return 0;
                                else return -1;
                            else if ($val2 === $sort_val) return 1;
                            return 0;
                        });
                else
                    usort($indicators,
                        function (Indicator $ind1, Indicator $ind2) use ($sort_key) {
                            return strcmp($ind1->getParam($sort_key),
                                            $ind2->getParam($sort_key));
                        });
        }
        return $indicators;
    }


    public function unsetAllIndicators()
    {
        $this->_indicators = [];
        return $this;
    }


    public function calculateIndicators()
    {
        foreach ($this->getIndicators() as &$indicator)
            $indicator->checkAndRun();
        return $this;
    }


    public function dumpIndicators()
    {
        $dump = '';
        foreach ($this->getIndicators() as $indicator)
            $dump .= var_export($indicator->getParams(), true)."\n";
        return $dump;
    }
}
