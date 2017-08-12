<?php

namespace GTrader;

trait Scheduled
{
    protected function scheduleEnabled()
    {
        $class = $this->getShortClass();
        $file = storage_path('run/'.$class);
        clearstatcache(true, $file);
        if (is_file($file)) {
            //Log::debug('file exists', $file);
            return true;
        }
        Log::info($class.' schedule disabled, file not present: '.$file);
        return false;
    }
}
