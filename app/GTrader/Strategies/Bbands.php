<?php

namespace GTrader\Strategies;

use GTrader\Indicator;

class Bbands extends Simple
{
    protected function createDefaultIndicators()
    {
        $ohlc = $this->getOrAddIndicator('Ohlc');
        $open = $ohlc->getSignature('open');

        $bb     = $this->getOrAddIndicator('Bbands', ['input_source' => $open]);
        $upper  = $bb->getSignature('Upper');
        $middle = $bb->getSignature('Middle');
        $lower  = $bb->getSignature('Lower');

        $mid     = $this->getOrAddIndicator('Mid');
        $mid_sig = $mid->getSignature();

        $signals = Indicator::make(
            'Signals',
            [
                'indicator' => [
                    'strategy_id'               => 0,           // Custom Settings
                    'input_open_long_a'         => $open,
                    'open_long_cond'            => '>',
                    'input_open_long_b'         => $upper,
                    'input_open_long_source'    => $mid_sig,
                    'input_close_long_a'        => $open,
                    'close_long_cond'           => '<',
                    'input_close_long_b'        => $middle,
                    'input_close_long_source'   => $mid_sig,
                    'input_open_short_a'        => $open,
                    'open_short_cond'           => '<',
                    'input_open_short_b'        => $lower,
                    'input_open_short_source'   => $mid_sig,
                    'input_close_short_a'       => $open,
                    'close_short_cond'          => '>',
                    'input_close_short_b'       => $middle,
                    'input_close_short_source'  => $mid_sig,
                ],
            ]
        );
        $signals->addAllowedOwner($this);
        $signals = $this->addIndicator($signals);
        $signals->addRef('root');

        $ohlc->visible(true);
        $bb->visible(true);
        $mid->visible(true);
        $signals->visible(true);

        return $this;
    }
}
