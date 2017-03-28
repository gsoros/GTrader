<?php

namespace GTrader;

class Lock
{
    use Skeleton;

    protected $locks = [];

    public static function obtain(string $lock)
    {
        return self::singleton()->lock($lock);
    }


    public static function release(string $lock)
    {
        return self::singleton()->unlock($lock);
    }


    protected function lock(string $lock)
    {
        $lockfile = fopen($this->path($lock), 'c+');
        if (!$lockfile || !flock($lockfile, LOCK_EX | LOCK_NB)) {
            return false;
        }
        $this->locks[$lock] = $lockfile;
        return true;
    }


    protected function unlock(string $lock)
    {
        if (!isset($this->locks[$lock])) {
            return false;
        }
        if (!($lockfile = $this->locks[$lock])) {
            return false;
        }
        flock($lockfile, LOCK_UN);
        fclose($lockfile);
        unlink($this->path($lock));
        unset($this->locks[$lock]);
        return true;
    }


    protected function path(string $lock)
    {
        $dir = $this->getParam('path');
        if (!is_dir($dir)) {
            if (!mkdir($dir)) {
                throw new \Exception('Failed to create directory '.$dir);
            }
        }
        if (!($lock = addslashes(str_replace(DIRECTORY_SEPARATOR, '', trim($lock))))) {
            throw new \Exception('Empty lock');
        }

        return $dir.DIRECTORY_SEPARATOR.$lock;
    }
}
