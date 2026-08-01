<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astrology;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astrology\AspectCalculator;
use Vittix\Panchang\Enum\Body;

final class AspectCalculatorTest extends TestCase
{
    private AspectCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new AspectCalculator();
    }

    public function testGeneralAspects(): void
    {
        // Sun in House 1 aspects House 7
        $this->assertSame(
            [7],
            $this->calculator->calculateAspectedHouses(Body::Sun, 1)
        );

        // Venus in House 10 aspects House 4
        $this->assertSame(
            [4],
            $this->calculator->calculateAspectedHouses(Body::Venus, 10)
        );
    }

    public function testMarsAspects(): void
    {
        // Mars in House 1 aspects 4, 7, 8
        $this->assertSame(
            [4, 7, 8],
            $this->calculator->calculateAspectedHouses(Body::Mars, 1)
        );

        // Mars in House 12 aspects 3, 6, 7
        $this->assertSame(
            [3, 6, 7],
            $this->calculator->calculateAspectedHouses(Body::Mars, 12)
        );
    }

    public function testJupiterAspects(): void
    {
        // Jupiter in House 1 aspects 5, 7, 9
        $this->assertSame(
            [5, 7, 9],
            $this->calculator->calculateAspectedHouses(Body::Jupiter, 1)
        );
    }

    public function testSaturnAspects(): void
    {
        // Saturn in House 1 aspects 3, 7, 10
        $this->assertSame(
            [3, 7, 10],
            $this->calculator->calculateAspectedHouses(Body::Saturn, 1)
        );

        // Saturn in House 8 aspects 10, 2, 5
        $this->assertSame(
            [2, 5, 10],
            $this->calculator->calculateAspectedHouses(Body::Saturn, 8)
        );
    }
}
