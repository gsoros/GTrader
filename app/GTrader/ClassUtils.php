<?php

namespace GTrader;

trait ClassUtils
{
    protected static function getClassConf(string $class, $key = null)
    {
        //echo PHP_EOL.'getClassConf('.$class.', '.$key.')';
        if (!is_null($key)) {
            $key = '.'.$key;
        }
        //echo PHP_EOL.'config('.str_replace('\\', '.', $class).$key.')';
        $conf = config(str_replace('\\', '.', $class).$key);
        return $conf;
    }


    protected static function loadConfRecursive(string $class)
    {
        $parent_conf = [];
        if ($parent = get_parent_class($class)) {
            $parent_conf = self::loadConfRecursive($parent);
            foreach (['children_ns', 'default_child'] as $no_inherit) {
                if (isset($parent_conf[$no_inherit])) {
                    unset($parent_conf[$no_inherit]);
                }
            }
        }
        if (is_array($conf = self::getClassConf($class))) {
            return array_replace_recursive($parent_conf, $conf);
        }
        return $parent_conf;
    }


    public function getShortClass()
    {
        $reflect = new \ReflectionClass($this);
        return $reflect->getShortName();
    }


    public function isClass(string $class)
    {
        //return $class === get_class($this) || is_subclass_of($this, $class);
        return $this instanceof $class;
    }


    public function oid()
    {
        return $this->getShortClass().'('.md5(spl_object_hash($this)).')';
    }


    public function methodNotImplemented()
    {
        $d = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        Log::error(
            'Method '.
            (is_array($d[1]) ? $d[1]['function'] ?? 'unknown' : 'unknown').
            ' not implemented in '.
            get_called_class()
        );
    }
}
