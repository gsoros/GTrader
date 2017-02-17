<?php

/*
    GTrader - A Trading Strategy Tester and Bot
    Copyright (C) 2017  G Soros <gsoros@gmail.com>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

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
            {
                error_log('No default child class for '.get_called_class());
                $class = $called;
            }

        }
        if ($class !== $called)
            $class = __NAMESPACE__.'\\'
                    .self::getClassConf($called, 'children_ns').'\\'
                    .$class;

        return new $class($params);
    }


    /**
     * Create and return a singleton.
     *
     * @return \GTrader\*
     */
    public static function singleton()
    {
        static $singleton;

        if (!is_object($singleton))
            $singleton = self::make();
        return $singleton;
    }


    /**
     * Provide support for magic static calls.
     *
     * @return mixed
     */
    public static function __callStatic($method, $params)
    {
        $singleton = self::singleton();
        error_log('callStatic: '.get_class($singleton).'->'.$method.'('.serialize($params).')');
        if (!is_callable([$singleton, $method]))
            throw new \Exception(get_class($singleton).'->'.$method.'() is not callable.');
        $params = isset($params[0]) ? $params[0] : [];
        return $singleton->$method($params);
    }
}
