<?php

namespace GTrader;

use Illuminate\Support\Arr;

class Event
{
    protected static $subscriptions = [];


    public static function subscribe(string $key, callable $func): int
    {
        //dump('subscribe '.$key.' '.$func[0]->debugObjId());
        if (static::subscribed($key, $func)) {
            return 0;
        }
        static::$subscriptions[$key][] = $func;
        return 1;
    }


    public static function unsubscribe(string $key, callable $func): int
    {
        //dump('unsubscribe '.$key.' '.$func[0]->debugObjId());
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
        //dump('Event::dispatch('.$object->debugObjId().', '.$key.')', $event);
        $dispatched = 0;
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
        //dump('Subscribed? '.$func[0]->debugObjId());
        if (!count($subs = static::subscriptions($key))) {
            return false;
        }
        return in_array($func, $subs);
    }


    protected static function subscriptions(string $key): array
    {
        if (!$subs = Arr::get(static::$subscriptions, $key)) {
            return [];
        }
        if (!is_array($subs)) {
            return [];
        }
        return $subs;
    }


    public static function subscriptionCount(): int
    {
        return count(static::$subscriptions, COUNT_RECURSIVE);
    }


    public static function dumpSubscriptions()
    {
        dump(static::$subscriptions);
    }
}
