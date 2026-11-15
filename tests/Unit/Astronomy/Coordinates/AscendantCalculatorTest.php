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
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\Tests\Support\TestLocations;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\ValueObject\GeoLocation;

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

    /**
     * At sunrise the Sun is on the eastern horizon, so the Ascendant must
     * coincide with the Sun's sidereal longitude (here ~208°, Tula).
     */
    public function testSunriseAscendantMatchesSunLongitude(): void
    {
        $calc = $this->makeCalculator();
        $engine = new PurePhpAstronomyEngine();
        $timeFactory = new AstroTimeFactory();
        $location = TestLocations::ahmedabad();

        $solar = $engine->solarEvents(
            new DateTimeImmutable('2026-11-15', new DateTimeZone('Asia/Kolkata')),
            $location,
            new DateTimeZone('Asia/Kolkata'),
        );
        self::assertNotNull($solar->sunrise);

        $sunriseInstant = $timeFactory->fromDateTime($solar->sunrise);
        $ascendant = $calc->siderealLongitude($sunriseInstant, $location);
        $sun = $engine->siderealLongitude(Body::Sun, $sunriseInstant);

        self::assertEqualsWithDelta($sun, $ascendant, 2.0);
        // ~208° = Tula (Libra) as stated in the issue.
        self::assertEqualsWithDelta(208.0, $ascendant, 3.0);
    }

    /**
     * At sunset the Sun is setting in the west, so the Ascendant (east) must
     * be roughly opposite the Sun: Sun + 180°.
     */
    public function testSunsetAscendantIsOppositeSun(): void
    {
        $calc = $this->makeCalculator();
        $engine = new PurePhpAstronomyEngine();
        $timeFactory = new AstroTimeFactory();
        $location = TestLocations::ahmedabad();

        $solar = $engine->solarEvents(
            new DateTimeImmutable('2026-11-15', new DateTimeZone('Asia/Kolkata')),
            $location,
            new DateTimeZone('Asia/Kolkata'),
        );
        self::assertNotNull($solar->sunset);

        $sunsetInstant = $timeFactory->fromDateTime($solar->sunset);
        $ascendant = $calc->siderealLongitude($sunsetInstant, $location);
        $sun = $engine->siderealLongitude(Body::Sun, $sunsetInstant);
        $opposite = fmod($sun + 180.0, 360.0);

        // Antipodal: compare the smaller of the two arcs.
        $diff = abs(fmod($ascendant - $opposite + 540.0, 360.0) - 180.0);
        self::assertLessThanOrEqual(2.0, $diff);
    }

    /**
     * At the equator (lat = 0) with RAMC = 0 the Ascendant must be 90°
     * tropical: tan(λ) = -cos(0) / (0 + cos(ε)·0) → atan2(1, 0) = 90°.
     */
    public function testEquatorRamcZeroGivesNinetyDegreesTropical(): void
    {
        $sidereal = new SiderealTimeCalculator();
        $nutation = new NutationCalculator();
        $calc = new AscendantCalculator(
            $sidereal,
            new ObliquityCalculator($nutation),
            new PurePhpAstronomyEngine(),
        );
        $timeFactory = new AstroTimeFactory();

        $equator = new GeoLocation(latitude: 0.0, longitude: 0.0, elevationMeters: 0.0);

        // Find a JD (searching a 24h window in 1s steps is too slow; use 60s
        // steps) where LST at longitude 0 crosses 0°, i.e. RAMC = 0.
        $startJd = (new AstroTimeFactory())->fromDateTime(
            new DateTimeImmutable('2026-06-01 00:00:00', new DateTimeZone('UTC'))
        )->julianDayUt;

        $targetJd = null;
        for ($jd = $startJd; $jd < $startJd + 1.0; $jd += 60.0 / 86400.0) {
            $lst = $sidereal->localMeanSiderealTime($jd, 0.0);
            $next = $sidereal->localMeanSiderealTime($jd + 60.0 / 86400.0, 0.0);
            if ($lst > 359.0 && $next < 1.0) {
                // Refine by bisection on the wrap point.
                $lo = $jd;
                $hi = $jd + 60.0 / 86400.0;
                for ($i = 0; $i < 40; $i++) {
                    $mid = ($lo + $hi) / 2.0;
                    $midLst = $sidereal->localMeanSiderealTime($mid, 0.0);
                    if ($midLst > 180.0) {
                        $lo = $mid;
                    } else {
                        $hi = $mid;
                    }
                }
                $targetJd = ($lo + $hi) / 2.0;
                break;
            }
        }
        self::assertNotNull($targetJd, 'LST wrap (RAMC = 0) not found in window');

        $instant = $timeFactory->fromJulianDay($targetJd);
        $tropical = $calc->tropicalLongitude($instant, $equator);

        self::assertEqualsWithDelta(90.0, $tropical, 0.5);
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
