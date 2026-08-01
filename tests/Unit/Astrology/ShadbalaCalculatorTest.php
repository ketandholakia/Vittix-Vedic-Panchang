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
            new NaisargikaBalaCalculator()
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

        $sunShadbala = $shadbala->planets[Body::Sun->value];

        // Sthana = 60 (Uchcha) + 60 (Kendra) = 120
        $this->assertEquals(120.0, $sunShadbala->sthanaBala);
        
        // Dik = 1st house. Weakest is 4th. Distance = 1 - 4 = -3 + 12 = 9. Inverse = 12 - 9 = 3. 3 * 10 = 30.
        $this->assertEquals(30.0, $sunShadbala->dikBala);

        // Naisargika = 60
        $this->assertEquals(60.0, $sunShadbala->naisargikaBala);

        // Kala, Chesta, Drik = 0
        $this->assertEquals(0.0, $sunShadbala->kalaBala);
        $this->assertEquals(0.0, $sunShadbala->chestaBala);
        $this->assertEquals(0.0, $sunShadbala->drikBala);

        // Total
        $this->assertEquals(120 + 30 + 60, $sunShadbala->getTotalVirupas());
        $this->assertEquals((120 + 30 + 60) / 60.0, $sunShadbala->getTotalRupas());
    }
}
