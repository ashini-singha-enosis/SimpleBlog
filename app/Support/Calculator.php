<?php

namespace App\Support;

class Calculator
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function add(int|float $num1, int|float $num2) : int|float {
        return $num1 + $num2;
    }

}
