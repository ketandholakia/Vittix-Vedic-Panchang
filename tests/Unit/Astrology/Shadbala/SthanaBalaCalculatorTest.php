<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astrology\Shadbala;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astrology\Shadbala\SthanaBalaCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\ValueObject\PlanetPlacement;

class SthanaBalaCalculatorTest extends TestCase
{
    private SthanaBalaCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new SthanaBalaCalculator();
    }

    public function testSunExactExaltationAndKendra(): void
    {
        // Sun deep exaltation is 10 Aries.
        // In House 1 (Kendra = 60).
        // Total should be 60 (Uchcha) + 60 (Kendradi) = 120.
        $placement = new PlanetPlacement(Body::Sun, 10.0, Rashi::Mesha, 1);
        $this->assertEquals(120.0, $this->calculator->calculate($placement));
    }

    public function testSunExactDebilitationAndApoklima(): void
    {
        // Sun deep debilitation is 10 Libra (190.0 degrees).
        // Uchcha = 0.
        // In House 3 (Apoklima = 15).
        // Total should be 15.
        $placement = new PlanetPlacement(Body::Sun, 190.0, Rashi::Tula, 3);
        $this->assertEquals(15.0, $this->calculator->calculate($placement));
    }

    public function testMoonMidwayExaltationAndPanaphara(): void
    {
        // Moon deep exaltation is 3 Taurus (33.0). Deep debilitation is 3 Scorpio (213.0).
        // Placed at 123.0 (90 degrees from exaltation).
        // Distance from debilitation = 90.
        // Uchcha = 90 / 3 = 30.
        // In House 5 (Panaphara = 30).
        // Total should be 30 + 30 = 60.
        $placement = new PlanetPlacement(Body::Moon, 123.0, Rashi::Simha, 5);
        $this->assertEquals(60.0, $this->calculator->calculate($placement));
    }
}
