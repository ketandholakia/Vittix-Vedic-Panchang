<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astrology\Shadbala;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astrology\Shadbala\ChestaBalaCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\ValueObject\PlanetPlacement;

class ChestaBalaCalculatorTest extends TestCase
{
    private ChestaBalaCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ChestaBalaCalculator();
    }

    public function testSunAndMoonReturnZero(): void
    {
        $sun = new PlanetPlacement(Body::Sun, 0.0, Rashi::Mesha, 1, [], null, 0.0, 1.0);
        $moon = new PlanetPlacement(Body::Moon, 0.0, Rashi::Mesha, 1, [], null, 0.0, 13.0);
        
        $this->assertEquals(0.0, $this->calculator->calculate($sun));
        $this->assertEquals(0.0, $this->calculator->calculate($moon));
    }

    public function testRetrogradeGetsFullStrength(): void
    {
        // Jupiter moving backwards
        $jupiter = new PlanetPlacement(Body::Jupiter, 0.0, Rashi::Mesha, 1, [], null, 0.0, -0.1);
        
        $this->assertEquals(60.0, $this->calculator->calculate($jupiter));
    }

    public function testStationaryGetsHalfStrength(): void
    {
        // Mars nearly stationary
        $mars = new PlanetPlacement(Body::Mars, 0.0, Rashi::Mesha, 1, [], null, 0.0, 0.005);
        
        $this->assertEquals(30.0, $this->calculator->calculate($mars));
    }

    public function testDirectGetsBaselineStrength(): void
    {
        // Venus moving forward fast
        $venus = new PlanetPlacement(Body::Venus, 0.0, Rashi::Mesha, 1, [], null, 0.0, 1.2);
        
        $this->assertEquals(15.0, $this->calculator->calculate($venus));
    }
}
