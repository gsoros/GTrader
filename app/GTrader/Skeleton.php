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

trait Skeleton
{
    use HasParams;


    public function __construct(array $params = [])
    {
        $this->setParams(self::loadConfRecursive(get_class($this)));
        $this->setParams($params);
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
        if ($conf = self::getClassConf($class)) {
            return array_replace_recursive($parent_conf, $conf);
        }
        return $parent_conf;
    }


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


    public static function make(string $class = null, array $params = [])
    {
        $called = get_called_class();
        if (is_null($class)) {
            $class = self::getClassConf($called, 'default_child');
            if (!$class) {
                //error_log('No default child class for '.get_called_class());
                $class = $called;
            }

        }
        if ($class !== $called) {
            $class = __NAMESPACE__.'\\'
                    .self::getClassConf($called, 'children_ns').'\\'
                    .$class;
        }
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

        if (!is_object($singleton)) {
            $singleton = self::make();
        }
        return $singleton;
    }
}
