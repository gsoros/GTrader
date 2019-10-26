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

//use GTrader\Indicator; //?? why ??

trait Skeleton
{
    use HasParams, ClassUtils;

    public function skeletonConstruct(array $params = [])
    {
        $this->setParams(self::loadConfRecursive(get_class($this)));
        $this->setParams($params);
    }


    public static function make(string $class = null, array $params = [])
    {
        $orig_class = $class;
        $called = get_called_class();
        if (is_null($class)) {
            $class = self::getClassConf($called, 'default_child');
            if (!$class) {
                $class = $called;
            }
        }
        if ($class !== $called) {
            $class = __NAMESPACE__.'\\'
                .self::getClassConf($called, 'children_ns').'\\'.$class;
        }
        if (!class_exists($class)) {
            Log::critical(
                'Class '.$class.' does not exist.',
                __NAMESPACE__,
                self::getClassConf($called, 'children_ns'),
                $orig_class,
                $called
            );
            throw new \Exception('Class '.$class.' does not exist.');
            return null;
        }
        return new $class($params);
    }


    /**
     * Create and return a singleton.
     *
     * @return Base
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
