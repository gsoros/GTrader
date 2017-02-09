<?php

namespace GTrader;

trait HasIndicators
{
    protected $_indicators = [];
    
    
    public function addIndicator($indicator, array $params = [])
    {
        if (!is_object($indicator))
            $indicator = Indicator::make($indicator, $params);
        if ($this->hasIndicator($indicator->getSignature()))
            return $this;
        $indicator->setOwner($this);
        $this->_indicators[] = $indicator;
        return $this;
    }
    
    
    public function getIndicator(string $signature)
    {
        foreach ($this->_indicators as $indicator)
            if ($indicator->getSignature() === $signature)
                return $indicator;
        return null;
    }
    
 
    public function hasIndicator(string $signature)
    {
        return $this->getIndicator($signature) ? true : false;
    }
    
    
    public function unsetIndicator(string $signature) 
    {
        foreach ($this->_indicators as $indicator)
            if ($indicator->getSignature() === $signature)
                unset($this->_indicators[$key]);
        return $this;
    }
    
        
    public function getIndicators()
    {
        return $this->_indicators;
    }
    
    
    public function unsetIndicators() 
    {
        $this->_indicators = [];
        return $this;
    }
    
    
    public function calculateIndicators()
    {
        foreach ($this->_indicators as &$indicator)
            $indicator->checkAndRun();
        return $this;
    }
}
