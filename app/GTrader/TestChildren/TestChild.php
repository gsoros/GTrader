<?php

namespace GTrader\TestChildren;

use GTrader\TestClass;

class TestChild extends TestClass
{
    protected $protected_prop = 'success';
    
    public function someMethod()
    {
        return $this->protected_prop;
    }
}
