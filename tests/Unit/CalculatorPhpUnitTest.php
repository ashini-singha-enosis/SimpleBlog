<?php

namespace Tests\Unit;

use App\Support\Calculator;
use Override;
use PHPUnit\Framework\TestCase;

class CalculatorPhpUnitTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    private Calculator $calculator;
    public function test_example(): void
    {
        $this->assertTrue(true);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new Calculator();
    }

    public function test_adds_two_integers():void
    {
        $result = $this->calculator->add(10, 80); 

        $this->assertSame(30, $result);

    }
}
