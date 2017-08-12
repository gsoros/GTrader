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
        return new Tiktaalik();
    }


    public function mutate(): Evolvable
    {
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
        return floatval($this->getParam('mutation_rate'));
    }


    public function setMutationRate(float $rate): Evolvable
    {
        $this->getParam('mutation_rate', $rate);
        return $this;
    }
}
