<?php

namespace GTrader;


class Store extends Base
{
    protected $store = [];


    public static function &getStatic(string $slot, $initial = null)
    {
        if (!strlen($slot)) {
            Log::error('slot must be named');
            return null;
        }

        $store = &self::singleton()->store;

        if (!isset($store[$slot])) {
            $store[$slot] = $initial;
        }

        return $store[$slot];
    }
}
