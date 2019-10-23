<?php

namespace GTrader;

interface Evolvable
{
    public function mate(Evolvable $partner): Evolvable;

    public function mutate(): Evolvable;

    public function fitness($set = null);
}
