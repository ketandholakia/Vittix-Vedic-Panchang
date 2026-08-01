<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astrology\Shadbala;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astrology\Shadbala\NaisargikaBalaCalculator;
use Vittix\Panchang\Enum\Body;

class NaisargikaBalaCalculatorTest extends TestCase
{
    public function testNaisargikaBalaValues(): void
    {
        $calculator = new NaisargikaBalaCalculator();

        $this->assertEquals(60.0, $calculator->calculate(Body::Sun));
        $this->assertEquals(51.428, $calculator->calculate(Body::Moon));
        $this->assertEquals(42.857, $calculator->calculate(Body::Venus));
        $this->assertEquals(34.285, $calculator->calculate(Body::Jupiter));
        $this->assertEquals(25.714, $calculator->calculate(Body::Mercury));
        $this->assertEquals(17.142, $calculator->calculate(Body::Mars));
        $this->assertEquals(8.571, $calculator->calculate(Body::Saturn));
    }
}
