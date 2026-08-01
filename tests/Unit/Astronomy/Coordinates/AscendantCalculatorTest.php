<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astronomy\Coordinates;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Coordinates\AscendantCalculator;
use Vittix\Panchang\Astronomy\Coordinates\NutationCalculator;
use Vittix\Panchang\Astronomy\Coordinates\ObliquityCalculator;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Astronomy\Sidereal\SiderealTimeCalculator;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\Tests\Support\TestLocations;
use Vittix\Panchang\Time\AstroTimeFactory;

final class AscendantCalculatorTest extends TestCase
{
    private function makeCalculator(): AscendantCalculator
    {
        $nutation = new NutationCalculator();

        return new AscendantCalculator(
            new SiderealTimeCalculator(),
            new ObliquityCalculator($nutation),
            new PurePhpAstronomyEngine(),
        );
    }

    public function testCalculateReturnsAValidRashiAndInRangeDegree(): void
    {
        $calc = $this->makeCalculator();
        $instant = (new AstroTimeFactory())->fromDateTime(
            new DateTimeImmutable('2000-06-15 12:00:00', new DateTimeZone('Asia/Kolkata'))
        );

        $result = $calc->calculate($instant, TestLocations::ahmedabad());

        self::assertContains($result->rashi, Rashi::cases());
        self::assertGreaterThanOrEqual(0.0, $result->degreeInSign);
        self::assertLessThan(30.0, $result->degreeInSign);
        self::assertEqualsWithDelta(
            $result->siderealLongitude,
            ($result->rashi->value * 30.0) + $result->degreeInSign,
            1e-9
        );
    }

    public function testAscendantAdvancesOverTheDay(): void
    {
        // Over 24 hours the Ascendant must complete one full 360° cycle.
        // Sampling every 6 hours should show forward progress (allowing for
        // one wrap past 360°), never backward motion beyond the wrap.
        $calc = $this->makeCalculator();
        $timeFactory = new AstroTimeFactory();
        $location = TestLocations::ahmedabad();

        $longitudes = [];
        foreach (['00:00', '06:00', '12:00', '18:00'] as $time) {
            $instant = $timeFactory->fromDateTime(
                new DateTimeImmutable("2000-06-15 {$time}:00", new DateTimeZone('Asia/Kolkata'))
            );
            $longitudes[] = $calc->siderealLongitude($instant, $location);
        }

        for ($i = 1; $i < count($longitudes); $i++) {
            $delta = fmod($longitudes[$i] - $longitudes[$i - 1] + 360.0, 360.0);
            // Roughly 90° of ecliptic per 6h at moderate latitudes, but
            // oblique ascension means this varies by sign; just assert
            // forward progress within a generous bound.
            self::assertGreaterThan(0.0, $delta);
            self::assertLessThan(180.0, $delta);
        }
    }

    public function testTropicalMinusAyanamshaEqualsSidereal(): void
    {
        $engine = new PurePhpAstronomyEngine();
        $nutation = new NutationCalculator();
        $calc = new AscendantCalculator(
            new SiderealTimeCalculator(),
            new ObliquityCalculator($nutation),
            $engine,
        );

        $instant = (new AstroTimeFactory())->fromDateTime(
            new DateTimeImmutable('1990-01-15 08:30:00', new DateTimeZone('Asia/Kolkata'))
        );
        $location = TestLocations::ahmedabad();

        $tropical = $calc->tropicalLongitude($instant, $location);
        $sidereal = $calc->siderealLongitude($instant, $location);
        $ayanamsha = $engine->ayanamsa($instant);

        self::assertEqualsWithDelta(
            fmod($tropical - $ayanamsha + 360.0, 360.0),
            $sidereal,
            1e-9
        );
    }
}
