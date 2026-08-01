<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astrology\Shadbala;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astrology\Shadbala\AyanaBalaCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\ValueObject\PlanetPlacement;

class AyanaBalaCalculatorTest extends TestCase
{
    private AyanaBalaCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new AyanaBalaCalculator();
    }

    public function testSunStrongInNorth(): void
    {
        // Sun at +24 declination (maximum North)
        $placement = new PlanetPlacement(Body::Sun, 90.0, Rashi::Karka, 4, [], null, 24.0, 1.0);
        $bala = $this->calculator->calculate($placement);
        
        // (24 + 24) / 48 * 60 = 60
        $this->assertEqualsWithDelta(60.0, $bala, 0.01);
    }

    public function testSunWeakInSouth(): void
    {
        // Sun at -24 declination (maximum South)
        $placement = new PlanetPlacement(Body::Sun, 270.0, Rashi::Makara, 10, [], null, -24.0, 1.0);
        $bala = $this->calculator->calculate($placement);
        
        // (24 + -24) / 48 * 60 = 0
        $this->assertEqualsWithDelta(0.0, $bala, 0.01);
    }

    public function testMoonStrongInSouth(): void
    {
        // Moon at -24 declination (maximum South)
        $placement = new PlanetPlacement(Body::Moon, 270.0, Rashi::Makara, 10, [], null, -24.0, 13.0);
        $bala = $this->calculator->calculate($placement);
        
        // (24 - -24) / 48 * 60 = 60
        $this->assertEqualsWithDelta(60.0, $bala, 0.01);
    }
}
