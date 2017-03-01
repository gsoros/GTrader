<?php

namespace GTrader;

use Illuminate\Support\Arr;

trait HasParams
{
    protected $_params = [];



    public function getParam(string $key, $default = null)
    {
        return Arr::get($this->_params, $key, $default);
    }


    public function getParams()
    {
        return $this->_params;
    }


    public function hasParam(string $key)
    {
        return Arr::has($this->_params, $key);
    }


    public function getParamsExcept($key = null)
    {
        if (is_null($key)) return $this->getParams();
        return Arr::except($this->getParams(), $key);
    }


    public function setParam(string $key = null, $value = null)
    {
        Arr::set($this->_params, $key, $value);
        return $this;
    }


    public function setParams(array $params = [])
    {
        $this->_params = array_replace_recursive($this->_params, $params);
        return $this;
    }


    public function mergeParams(array $params = [])
    {
        $this->_params = array_replace_recursive($this->_params, $params);
        return $this;
    }
}
