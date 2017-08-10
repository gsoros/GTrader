<?php

namespace GTrader;

interface Evolvable
{
    public function mate(Evolvable $partner): Evolvable;

    public function mutate(): Evolvable;

    public function getFitness(): float;

    public function setFitness(float $fitness): Evolvable;

    public function getMutationRate(): float;

    public function setMutationRate(float $rate): Evolvable;
}
