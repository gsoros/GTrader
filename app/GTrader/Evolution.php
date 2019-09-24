<?php

namespace GTrader;

interface Evolution
{
    public function introduce(Evolvable $organism): Evolution;

    public function raiseGeneration(int $size): Evolution;

    public function evaluate(Evolvable $organism): Evolution;

    public function killIndividual($index): Evolution;

    public function killGeneration(): Evolution;

    public function selection(int $survivors = 2): Evolution;

    public function generation(): array;
}
