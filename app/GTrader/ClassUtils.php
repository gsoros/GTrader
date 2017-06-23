<?php

namespace GTrader;

trait ClassUtils
{
    protected static function getClassConf(string $class, $key = null)
    {
        //error_log('getClassConf('.$class.', '.$key.')');
        if (!is_null($key)) {
            $key = '.'.$key;
        }
        $conf = \Config::get(str_replace('\\', '.', $class).$key);
        return $conf;
    }


    public function getShortClass()
    {
        $reflect = new \ReflectionClass($this);
        return $reflect->getShortName();
    }


    public function isClass(string $class)
    {
        return $class === get_class($this) || is_subclass_of($this, $class);
    }


    public function debugObjId()
    {
        return $this->getShortClass().' '.md5(spl_object_hash($this));
    }
}
