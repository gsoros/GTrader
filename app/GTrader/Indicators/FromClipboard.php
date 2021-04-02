<?php

namespace GTrader\Indicators;

use GTrader\Indicator;

class FromClipboard extends Indicator
{
    public function calculate(bool $force_rerun = false)
    {
        return $this;
    }
}
