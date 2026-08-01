<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astrology\Shadbala;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astrology\Shadbala\DikBalaCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\ValueObject\PlanetPlacement;

class DikBalaCalculatorTest extends TestCase
{
    private DikBalaCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new DikBalaCalculator();
    }

    public function testSunDikBalaMaxIn10thHouse(): void
    {
        $placement = new PlanetPlacement(Body::Sun, 0.0, Rashi::Mesha, 10);
        $this->assertEquals(60.0, $this->calculator->calculate($placement));
    }

    public function testSunDikBalaMinIn4thHouse(): void
    {
        $placement = new PlanetPlacement(Body::Sun, 0.0, Rashi::Mesha, 4);
        $this->assertEquals(0.0, $this->calculator->calculate($placement));
    }

    public function testSunDikBalaIn7thHouse(): void
    {
        // 7th is 3 houses away from 4th (weakest), so 3 * 10 = 30
        $placement = new PlanetPlacement(Body::Sun, 0.0, Rashi::Mesha, 7);
        $this->assertEquals(30.0, $this->calculator->calculate($placement));
    }

    public function testMoonDikBalaMaxIn4thHouse(): void
    {
        $placement = new PlanetPlacement(Body::Moon, 0.0, Rashi::Mesha, 4);
        $this->assertEquals(60.0, $this->calculator->calculate($placement));
    }
}
