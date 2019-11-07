<?php

namespace GTrader\Strategies;

use Illuminate\Http\Request;

use GTrader\Evolvable;
use GTrader\Indicator;
use GTrader\Log;

class Tiktaalik extends Simple implements Evolvable
{
    use Trainable;


    public function __clone()
    {
        // do not make a copy of $candles by calling HasCandles::__clone()
        $this->__HasIndicators__clone();
        $this->__HasCache__clone();
    }


    public function toHTML(string $content = null)
    {
        return parent::toHTML($content);
    }


    public function mate(Evolvable $partner): Evolvable
    {
        $fitness = ($this->fitness() ?? 1);
        $partner_fitness = ($partner->fitness() ?? 1);
        $partner_weight =  1 / ($fitness + $partner_fitness) * $partner_fitness;
        dd('Tiktaalik::mate()', $fitness, $partner_fitness, $partner_weight);

        $offspring = clone $this;

        foreach ($offspring->getIndicators() as $ind) {
            $sig = $ind->getSignature();
            $anc = $this->getIndicatorAncestor($sig);
            if ($partner_ind = $partner->getIndicatorByAncestor($anc)) {
                $ind->crossover($partner_ind, $partner_weight);
                $new_sig = $ind->getSignature();
                if ($new_sig !== $sig) {
                    $this->setIndicatorAncestor($new_sig, $anc);
                }
            }
        }

        $offspring->setParam('evaluated', false);
        return $offspring;
    }


    public function mutate(): Evolvable
    {
        // TODO add/remove indicators

        foreach (
            array_merge(
                $this->getIndicatorsFilteredSorted([
                    ['class', 'not', 'Balance'],
                    ['class', 'not', 'Signals'],
                ]),
                [$this->getSignalsIndicator()]
            ) as $ind) {

            $ind->mutate(
                $this->getParam('mutation_rate'),
                $this->getParam('max_nesting')
            );
        }

        $this->cleanCache();

        return $this;
    }


    public function fitness($set = null)
    {
        if (null === $set) {
            return floatval($this->getParam('fitness'));
        }
        $this->setParam('fitness', $set);
        return $this;
    }


    public function viewIndicatorsList(Request $request = null, array $options = [])
    {
        return parent::viewIndicatorsList($request, [
            'view' => ['mutability' => true],
        ]);
    }


    public function viewSignalsForm(array $options = [])
    {
        return parent::viewSignalsForm([
            'view' => ['mutability' => true],
        ]);
    }


    public function handleIndicatorSaveRequest(Request $request)
    {
        $uid = $this->getParam('uid');
        if (!isset($request->{'mutable_'.$uid}) ||
            !is_array($request->{'mutable_'.$uid}) ||
            !count($request->{'mutable_'.$uid})) {
            return parent::handleIndicatorSaveRequest($request);
        }
        $mutable = [];
        foreach ($request->{'mutable_'.$uid} as $key => $val) {
            $mutable[$key] = intval($val);
        }
        $request->merge([
            'mutable' => json_encode($mutable),
        ]);
        return parent::handleIndicatorSaveRequest($request);
    }


    protected function createDefaultIndicators()
    {
        $ohlc       = $this->getOrAddIndicator('Ohlc');
        $ohlc_open  = $ohlc->getSignature('open');

        $ema1 = $this->getOrAddIndicator('Ema', ['input_source' => $ohlc_open, 'length'  => 9]);
        $ema2 = $this->getOrAddIndicator('Ema', ['input_source' => $ohlc_open, 'length'  => 29]);
        $ema3 = $this->getOrAddIndicator('Ema', ['input_source' => $ohlc_open, 'length'  => 49]);
        $mid  = $this->getOrAddIndicator('Mid');

        $ema1_sig = $ema1->getSignature();
        $ema2_sig = $ema2->getSignature();
        $ema3_sig = $ema3->getSignature();
        $mid_sig  = $mid->getSignature();

        $signals = Indicator::make(
            'Signals',
            [
                'indicator' => [
                    'strategy_id'               => 0,           // Custom Settings
                    'input_open_long_a'         => $ema1_sig,
                    'open_long_cond'            => '>',
                    'input_open_long_b'         => $ema3_sig,
                    'input_open_long_source'    => $mid_sig,
                    'input_close_long_a'        => $ema1_sig,
                    'close_long_cond'           => '<',
                    'input_close_long_b'        => $ema2_sig,
                    'input_close_long_source'   => $mid_sig,
                    'input_open_short_a'        => $ema1_sig,
                    'open_short_cond'           => '<',
                    'input_open_short_b'        => $ema3_sig,
                    'input_open_short_source'   => $mid_sig,
                    'input_close_short_a'       => $ema1_sig,
                    'close_short_cond'          => '>',
                    'input_close_short_b'       => $ema2_sig,
                    'input_close_short_source'  => $mid_sig,
                ],
            ]
        );
        $signals->addAllowedOwner($this);
        $signals = $this->addIndicator($signals);
        $signals->addRef('root');

        $ema1->visible(true);
        $ema2->visible(true);
        $ema3->visible(true);

        $ema1->mutable('length', true);
        $ema2->mutable('length', true);
        $ema3->mutable('length', true);
        $signals->mutable('input_open_long_a', true);
        $signals->mutable('open_long_cond', true);
        $signals->mutable('input_open_long_b', true);
        $signals->mutable('input_close_long_a', true);
        $signals->mutable('close_long_cond', true);
        $signals->mutable('input_close_long_b', true);
        $signals->mutable('input_open_short_a', true);
        $signals->mutable('open_short_cond', true);
        $signals->mutable('input_open_short_b', true);
        $signals->mutable('input_close_short_a', true);
        $signals->mutable('close_short_cond', true);
        $signals->mutable('input_close_short_b', true);

        return $this;
    }
}
