<?php

namespace GTrader\Strategies;

use GTrader\Evolvable;

class Tiktaalik extends Simple implements Evolvable
{
    use Trainable;


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
        return $offspring;
    }


    public function mutate(): Evolvable
    {
        // TODO add/remove indicators

        $inds = $this->getIndicators();
        //dump('Tiktaalik mutate(), inds: '.count($inds));
        foreach ($inds as $ind) {
            $sig = $ind->getSignature();
            $anc = $this->getIndicatorAncestor($sig);
            //dump('Tiktaalik::mutate() mutating '.$sig);
            $ind->mutate($this->getMutationRate());
            $new_sig = $ind->getSignature();
            if ($new_sig !== $sig) {
                $this->setIndicatorAncestor($new_sig, $anc);
            }
        }

        //TODO purge unused indicators

        return $this;
    }


    public function getFitness(): float
    {
        return floatval($this->getParam('fitness'));
    }


    public function setFitness(float $fitness): Evolvable
    {
        $this->setParam('fitness', $fitness);
        return $this;
    }


    public function getMutationRate(): float
    {
        return $this->getParam('mutation_rate');
    }


    public function setMutationRate(float $rate): Evolvable
    {
        $this->setParam('mutation_rate', $rate);
        return $this;
    }


    protected function getIndicatorAncestor(string $sig)
    {
        $ancs = $this->cached('ancestors', []);
        return ($ancs[$sig] ?? $sig);
    }


    protected function setIndicatorAncestor(string $sig, string $anc)
    {
        $ancs = $this->cached('ancestors', []);
        $ancs[$sig] = $anc;
        $this->cache('ancestors', $ancs);
        return $this;
    }


    public function getIndicatorByAncestor(string $anc)
    {
        $ancs = $this->cached('ancestors', []);
        if (false !== ($key = array_search($anc, $ancs))) {
            return $this->getIndicatorBySignature($key);
        }
        return $this->getIndicatorBySignature($anc);
    }


    public function clearAncestors()
    {
        $this->cache('ancestors', []);
        return $this;
    }
}
