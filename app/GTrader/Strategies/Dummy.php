<?php

namespace GTrader\Strategies;

use GTrader\Strategy;


class Dummy extends Strategy
{

    public function toHTML(string $content = null)
    {
        return parent::toHTML(
            view('Strategies/'.$this->getShortClass().'Form', ['strategy' => $this])
        );
    }

}
