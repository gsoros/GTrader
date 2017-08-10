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
    use HasParams, HasStatCache, ClassUtils;

    protected static $stat_cache = [];

    public function __construct(array $params = [])
    {
        $this->setParams(self::loadConfRecursive(get_class($this)));
        $this->setParams($params);
    }

    public function __destruct()
    {
        //dump('Skeleton::__destruct() ', $this);
    }

    public function __wakeup()
    {
    }


    public static function make(string $class = null, array $params = [])
    {
        $called = get_called_class();
        if (is_null($class)) {
            $class = self::getClassConf($called, 'default_child');
            if (!$class) {
                $class = $called;
            }
        }
        if ($class !== $called) {
            $class = __NAMESPACE__.'\\'
                    .self::getClassConf($called, 'children_ns').'\\'
                    .$class;
        }
        if (!class_exists($class)) {
            Log::error($called.'::make() Class '.$class.' does not exist');
            return null;
        }
        return new $class($params);
    }


    public function kill()
    {
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
