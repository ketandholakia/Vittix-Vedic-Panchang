<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astronomy\Sidereal;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Sidereal\SiderealTimeCalculator;
use Vittix\Panchang\Time\JulianDay;

final class SiderealTimeCalculatorTest extends TestCase
{
    public function testGmstAtJ2000IsKnownReferenceValue(): void
    {
        // At exactly JD 2451545.0 (J2000.0), T = 0 in the GMST polynomial,
        // so the result must equal the polynomial's constant term exactly
        // (Meeus, "Astronomical Algorithms" 2nd ed., eq. 12.4).
        $calc = new SiderealTimeCalculator();

        self::assertEqualsWithDelta(
            280.46061837,
            $calc->greenwichMeanSiderealTime(JulianDay::J2000),
            1e-6
        );
    }

    public function testGmstIsAlwaysNormalized(): void
    {
        $calc = new SiderealTimeCalculator();

        // A JD far from J2000 should still fold into [0, 360).
        $gmst = $calc->greenwichMeanSiderealTime(JulianDay::J2000 + 100000.123);

        self::assertGreaterThanOrEqual(0.0, $gmst);
        self::assertLessThan(360.0, $gmst);
    }

    public function testLocalMeanSiderealTimeAddsLongitude(): void
    {
        $calc = new SiderealTimeCalculator();
        $jd   = JulianDay::J2000 + 42.0;

        $gmst = $calc->greenwichMeanSiderealTime($jd);
        $lmst = $calc->localMeanSiderealTime($jd, 72.5714);

        self::assertEqualsWithDelta(
            fmod($gmst + 72.5714 + 360.0, 360.0),
            $lmst,
            1e-9
        );
    }

    public function testLocalMeanSiderealTimeWrapsForNegativeLongitude(): void
    {
        $calc = new SiderealTimeCalculator();

        $lmst = $calc->localMeanSiderealTime(JulianDay::J2000, -170.0);

        self::assertGreaterThanOrEqual(0.0, $lmst);
        self::assertLessThan(360.0, $lmst);
    }
}
