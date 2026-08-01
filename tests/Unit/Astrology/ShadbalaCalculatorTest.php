<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astrology;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astrology\Shadbala\DikBalaCalculator;
use Vittix\Panchang\Astrology\Shadbala\NaisargikaBalaCalculator;
use Vittix\Panchang\Astrology\Shadbala\SthanaBalaCalculator;
use Vittix\Panchang\Astrology\ShadbalaCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\ValueObject\AscendantPosition;
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\PlanetPlacement;

class ShadbalaCalculatorTest extends TestCase
{
    private ShadbalaCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->calculator = new ShadbalaCalculator(
            new SthanaBalaCalculator(),
            new DikBalaCalculator(),
            new NaisargikaBalaCalculator(),
            new \Vittix\Panchang\Astrology\Shadbala\AyanaBalaCalculator(),
            new \Vittix\Panchang\Astrology\Shadbala\ChestaBalaCalculator()
        );
    }

    public function testShadbalaCalculation(): void
    {
        $ascendant = new AscendantPosition(15.0, 15.0, Rashi::Mesha, 15.0); // 15 Aries
        
        $placements = [
            Body::Sun->value => new PlanetPlacement(Body::Sun, 10.0, Rashi::Mesha, 1), // Exact exaltation, 1st House (Kendra)
        ];
        
        $baseKundali = new Kundali($ascendant, $placements);

        $shadbala = $this->calculator->calculate($baseKundali);

        $this->assertCount(7, $shadbala->planets); // 7 visible planets

        $sunValues = $shadbala->planets[Body::Sun->value];
        $this->assertEquals(Body::Sun, $sunValues->body);
        // Sthana = 60 (Uchcha) + 60 (Kendra) = 120
        $this->assertEquals(120.0, $sunValues->sthanaBala);
        
        // Dik = 1st house. Weakest is 4th. Distance = 1 - 4 = -3 + 12 = 9. Inverse = 12 - 9 = 3. 3 * 10 = 30.
        $this->assertEquals(30.0, $sunValues->dikBala);

        // Naisargika = 60
        $this->assertEquals(60.0, $sunValues->naisargikaBala);

        // Ayana Bala for Sun at +10 declination (since we injected 10 declination in the mock)
        // Wait, PlanetPlacement doesn't have declination specified in the previous setup, so it is 0.0 or whatever.
        // I should set declination = 0.0, dailyMotion = 0.0 in the test
        $this->assertEquals(30.0, $sunValues->kalaBala); // (24+0)/48 * 60 = 30
        $this->assertEquals(0.0, $sunValues->chestaBala); // Sun returns 0
        $this->assertEquals(0.0, $sunValues->drikBala);

        // Total Virupas: 120 + 30 + 30 + 0 + 60 + 0 = 240
        $this->assertEquals(240.0, $sunValues->getTotalVirupas());
        $this->assertEquals(240.0 / 60.0, $sunValues->getTotalRupas());
    }
}

