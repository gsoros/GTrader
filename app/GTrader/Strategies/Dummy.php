<?php

namespace GTrader\Strategies;

use GTrader\Strategy;


class Dummy extends Strategy
{

    public function toHTML()
    {
        return parent::toHTML('Dummy Strategy Settings');
    }

}
