<?php

namespace GTrader;

use Illuminate\Support\Arr;

trait HasParams
{
    protected $params = [];


    /**
     * Get a parameter using dot notation.
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getParam(string $key, $default = null)
    {
        $current = $this->params;

        foreach (explode('.', $key) as $level) {
            if (!isset($current[$level])) {
                return $default;
            }
            $current = $current[$level];
        }

        return $current;

        // This version was 10X slower:
        // return Arr::get($this->params, $key, $default);
    }


    public function getParams()
    {
        return $this->params;
    }


    public function hasParam(string $key)
    {
        return Arr::has($this->params, $key);
    }


    public function getParamsExcept($key = null)
    {
        if (is_null($key)) {
            return $this->getParams();
        }
        return Arr::except($this->getParams(), $key);
    }


    public function setParam(string $key = null, $value = null)
    {
        Arr::set($this->params, $key, $value);
        return $this;
    }


    public function unsetParam(string $key = null)
    {
        Arr::forget($this->params, $key);
        return $this;
    }


    public function setParams(array $params = [])
    {
        $this->params = array_replace_recursive($this->params, $params);
        return $this;
    }
}
