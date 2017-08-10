<?php

namespace GTrader;

interface Evolution
{
    public function introduce(Evolvable $organism);

    public function raiseGeneration(int $size): Evolution;

    public function evaluateGeneration(): Evolution;

    public function generationEvaluated(): bool;

    public function killGeneration(int $num_survivors = 2): Evolution;

    public function fittest(int $num = 1);

    public function numPastGenerations(): int;
}
