<?php

namespace GTrader;

class Lock extends Skeleton {


    protected $locks = [];


    public static function obtain(string $lock)
    {
        return self::singleton()->add($lock);
    }


    public static function release(string $lock)
    {
        return self::singleton()->remove($lock);
    }


    protected function add(string $lock)
    {
        $lockfile = fopen($this->path($lock), 'c+');
        if (!$lockfile || !flock($lockfile, LOCK_EX | LOCK_NB))
            return false;
        $this->locks[$lock] = $lockfile;
        return true;
    }


    protected function remove(string $lock)
    {
        if (!isset($this->locks[$lock]))
            return false;
        flock($this->locks[$lock], LOCK_UN);
        fclose($this->locks[$lock]);
        unlink($this->path($lock));
        unset($this->locks[$lock]);
        return true;
    }

    protected function path(string $lock)
    {
        return storage_path(DIRECTORY_SEPARATOR.'tmp'.
                            DIRECTORY_SEPARATOR.'lock-'.$lock);
    }
}
