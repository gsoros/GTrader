<?php

namespace GTrader;

trait Scheduled
{
    protected function scheduleEnabled()
    {
        $class = $this->getShortClass();
        $file = storage_path('run/'.$class);
        if (is_file($file)) {
            return true;
        }
        Log::info($class.' schedule disabled, file not present: '.$file);
        return false;
    }
}
