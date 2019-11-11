<?php

namespace GTrader;

use Illuminate\Support\Arr;

class Event
{
    protected static $subscriptions = [];
    protected static $enabled = true;


    public static function subscribe(string $key, callable $func): int
    {
        //Log::debug('subscribe '.$key.' '.$func[0]->oid());
        if (!static::$enabled) {
            return 0;
        }
        if (static::subscribed($key, $func)) {
            return 0;
        }
        static::$subscriptions[$key][] = $func;
        return 1;
    }


    public static function unsubscribe(string $key, callable $func): int
    {
        //Log::debug('unsubscribe '.$key.' '.$func[0]->oid());
        if (!static::$enabled) {
            return 0;
        }
        if (!static::subscribed($key, $func)) {
            return 0;
        }
        foreach (static::$subscriptions[$key] as $sub_k => $sub_f) {
            if ($func === $sub_f) {
                unset(static::$subscriptions[$key][$sub_k]);
                return 1;
            }
        }
        return 0;
    }


    public static function dispatch($object, string $key, array $event): int
    {
        //dump('Event::dispatch('.$object->oid().', '.$key.')', $event);
        $dispatched = 0;
        if (!static::$enabled) {
            return $dispatched;
        }
        if (!count($subs = static::subscriptions($key))) {
            return $dispatched;
        }
        foreach ($subs as $sub) {
            if (!is_callable($sub)) {
                continue;
            }
            call_user_func($sub, $object, $event);
            $dispatched++;
        }
        return $dispatched;
    }


    protected static function subscribed(string $key, callable $func): bool
    {
        if (!static::$enabled) {
            return false;
        }
        //Log::debug('Subscribed? '.$key.' '.$func[0]->oid());
        if (!count($subs = static::subscriptions($key))) {
            return false;
        }
        return in_array($func, $subs);
    }


    protected static function subscriptions(string $key): array
    {
        if (!static::$enabled) {
            return [];
        }
        if (!$subs = Arr::get(static::$subscriptions, $key)) {
            return [];
        }
        if (!is_array($subs)) {
            return [];
        }
        return $subs;
    }


    public static function enable()
    {
        Log::info('Events enabled');
        static::$enabled = true;
    }


    public static function disable()
    {
        Log::warn('Events disabled');
        static::$enabled = false;
    }


    public static function clearSubscriptions()
    {
        static::$subscriptions = [];
    }


    public static function subscriptionCount(): int
    {
        if (!static::$enabled) {
            return 0;
        }
        return count(static::$subscriptions, COUNT_RECURSIVE);
    }


    public static function dumpSubscriptions()
    {
        $out = [];
        foreach (static::$subscriptions as $key => $subs) {
            foreach ($subs as $sub) {
                $out[$key][] = $sub[0]->oid().'->'.$sub[1].'()';
            }
        }
        dump($out);
    }

    /*
    public static function pruneSubscriptions()
    {
        foreach (static::$subscriptions as $key => $subs) {
            foreach ($subs as $sub) {
                $s = $sub[0];
                if (is_object($s)) {
                    if (method_exists($s, 'getOwner')) {
                        if (!is_object($s->getOwner())) {
                            Log::sparse($s->oid().' can be pruned');
                        }
                    }
                }
            }
        }
    }
    */
}
