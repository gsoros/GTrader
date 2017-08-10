<?php

namespace GTrader;

interface Gene
{
    public function crossover(Gene $gene, float $weight = .5): Gene;

    public function mutate(float $rate): Gene;
}
