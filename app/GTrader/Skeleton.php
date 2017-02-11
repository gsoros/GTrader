<?php

namespace GTrader;


class Skeleton
{
    use HasParams;


    public function __construct(array $params = [])
    {
        //dump('Construct() called: '.get_class($this).' parent: '.get_parent_class($this));
        if ($conf = self::getClassConf(get_parent_class($this)))
        {
            foreach (['children_ns', 'default_child'] as $no_inherit)
                if (isset($conf[$no_inherit]))
                    unset($conf[$no_inherit]);
            $this->setParams($conf);
        }
        if ($conf = self::getClassConf(get_class($this)))
            $this->setParams($conf);
        $this->setParams($params);
    }


    protected static function getClassConf(string $class, $key = null)
    {
        //dump('getClassConf('.$class.', '.$key.')');
        if (!is_null($key)) $key = '.'.$key;
        $conf = \Config::get(str_replace('\\', '.', $class).$key);
        return $conf;
    }


    public function getShortClass()
    {
        $reflect = new \ReflectionClass($this);
        return $reflect->getShortName();
    }


    public static function make(string $class = null, array $params = [])
    {
        $called = get_called_class();
        if (is_null($class))
        {
            $class = self::getClassConf($called, 'default_child');
            if (!$class)
                throw new \Exception('No default child class for '.get_called_class());
        }
        $class = __NAMESPACE__.'\\'
                    .self::getClassConf($called, 'children_ns').'\\'
                    .$class;
        return new $class($params);
    }


    /**
     * Create a new instance.
     *
     * @return \GTrader\*
     */
    public static function getInstance()
    {
        static $instance;

        if (!is_object($instance))
            $instance = self::make();
        return $instance;
    }


    /**
     * Provide support for magic static calls.
     *
     * @return mixed
     */
    public static function __callStatic($method, $params)
    {
        $instance = self::getInstance();
        //dump('callStatic: '.get_class($instance).'->'.$method.'()');
        if (!is_callable([$instance, $method]))
            throw new \Exception(get_class($instance).'->'.$method.'() is not callable.');
        $params = isset($params[0]) ? $params[0] : [];
        return $instance->$method($params);
    }
}
