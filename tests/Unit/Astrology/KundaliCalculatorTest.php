<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astrology;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astrology\KundaliCalculator;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Astronomy\Coordinates\AscendantCalculator;
use Vittix\Panchang\Astronomy\Coordinates\NutationCalculator;
use Vittix\Panchang\Astronomy\Coordinates\ObliquityCalculator;
use Vittix\Panchang\Astronomy\Sidereal\LahiriAyanamsha;
use Vittix\Panchang\Astronomy\Sidereal\SiderealTimeCalculator;
use Vittix\Panchang\Astrology\DignityCalculator;
use Vittix\Panchang\Astrology\AspectCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\ValueObject\GeoLocation;

final class KundaliCalculatorTest extends TestCase
{
    public function testCalculatesAccurateKundaliForJ2000(): void
    {
        $engine = new PurePhpAstronomyEngine(new LahiriAyanamsha());
        $nutation = new NutationCalculator();
        $ascendantCalculator = new AscendantCalculator(
            new SiderealTimeCalculator(),
            new ObliquityCalculator($nutation),
            $engine
        );

        $calculator = new KundaliCalculator($engine, $ascendantCalculator, new DignityCalculator(), new AspectCalculator());
        
        $instant = (new AstroTimeFactory())->fromDateTime(
            new DateTimeImmutable('2000-01-01 12:00:00', new DateTimeZone('UTC'))
        );
        $location = new GeoLocation(latitude: 28.6139, longitude: 77.2090); // New Delhi

        $kundali = $calculator->calculate($instant, $location);

        $this->assertNotNull($kundali);
        $this->assertCount(9, $kundali->placements);
        $this->assertArrayHasKey(Body::Sun->value, $kundali->placements);
        $this->assertArrayHasKey(Body::Moon->value, $kundali->placements);
        
        // Assert whole-sign house mathematical correctness
        $lagnaRashiIndex = $kundali->ascendant->rashi->value;
        foreach ($kundali->placements as $placement) {
            $expectedHouse = (($placement->rashi->value - $lagnaRashiIndex + 12) % 12) + 1;
            $this->assertSame($expectedHouse, $placement->house);
        }

        // Test filtering methods
        $sunHouse = $kundali->placements[Body::Sun->value]->house;
        $inHouse = $kundali->getPlacementsInHouse($sunHouse);
        $this->assertNotEmpty($inHouse);
        
        $sunRashi = $kundali->placements[Body::Sun->value]->rashi;
        $inRashi = $kundali->getPlacementsInRashi($sunRashi);
        $this->assertNotEmpty($inRashi);
    }
}
