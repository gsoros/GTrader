<?php

namespace GTrader\Strategies;

use GTrader\Evolvable;
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
        $fitness = ($this->getFitness() ?? 1);
        $partner_fitness = ($partner->getFitness() ?? 1);
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
}
