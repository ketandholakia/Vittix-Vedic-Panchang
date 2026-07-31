<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Time;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Time\JulianDay;

final class JulianDayTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Known Gregorian → JD pairs from Meeus Table 7.a
    // -------------------------------------------------------------------------

    /** @dataProvider gregorianToJdProvider */
    public function testGregorianToJd(int $y, int $m, float $d, float $expectedJd): void
    {
        $jd = JulianDay::fromGregorian($y, $m, $d);
        self::assertEqualsWithDelta($expectedJd, $jd, 0.0001);
    }

    /** @return array<array{int, int, float, float}> */
    public static function gregorianToJdProvider(): array
    {
        return [
            // J2000.0 epoch: 2000-Jan-1.5 TT = JD 2451545.0
            [2000,  1,  1.5, 2451545.0],
            // Meeus example 1: 1987-Apr-10 = JD 2446895.5  (Meeus p.62)
            [1987,  4, 10.0, 2446895.5],
            // Meeus example 2: 1988-Jun-19.5 = JD 2447332.0
            [1988,  6, 19.5, 2447332.0],
            // Meeus example 3: 1957-Oct-4.81 (Sputnik)
            [1957, 10,  4.81, 2436116.31],
            // 2000-Mar-1 (test month boundary)
            [2000,  3,  1.0, 2451604.5],
            // 1900-Jan-0.5 = JD 2415020.0 (standard)
            [1900,  1,  0.5, 2415020.0],
            // 2026-Jul-31 noon UTC (calculated from engine, verified)
            [2026,  7, 31.5, 2461253.0],
        ];
    }

    // -------------------------------------------------------------------------
    // JD → Gregorian round-trip
    // -------------------------------------------------------------------------

    public function testRoundTrip_J2000(): void
    {
        $jd = 2451545.0;
        [$y, $m, $d] = JulianDay::toGregorian($jd);
        self::assertSame(2000, $y);
        self::assertSame(1, $m);
        self::assertEqualsWithDelta(1.5, $d, 0.0001);
    }

    public function testRoundTrip_ArbitraryDate(): void
    {
        // Input JD → Gregorian → back to JD must be within 1 second
        $original = 2458849.125; // 2020-Jan-01 15:00 UTC
        [$y, $m, $d] = JulianDay::toGregorian($original);
        $roundTrip = JulianDay::fromGregorian($y, $m, $d);
        self::assertEqualsWithDelta($original, $roundTrip, 1.0 / 86400.0);
    }

    // -------------------------------------------------------------------------
    // fromUtc / toUtc
    // -------------------------------------------------------------------------

    public function testFromUtc_J2000(): void
    {
        $dt = new DateTimeImmutable('2000-01-01 12:00:00', new DateTimeZone('UTC'));
        self::assertEqualsWithDelta(JulianDay::J2000, JulianDay::fromUtc($dt), 0.0001);
    }

    public function testFromUtc_ColkataOffset(): void
    {
        // Asia/Kolkata is UTC+05:30. 2026-07-31 00:00 IST = 2026-07-30 18:30 UTC
        $dt = new DateTimeImmutable('2026-07-31 00:00:00', new DateTimeZone('Asia/Kolkata'));
        $jd = JulianDay::fromUtc($dt);
        // Expected: Gregorian 2026-07-30 18:30 UTC
        $expectedJd = JulianDay::fromGregorian(2026, 7, 30.0 + 18.5 / 24.0);
        self::assertEqualsWithDelta($expectedJd, $jd, 1.0 / 86400.0);
    }

    public function testToUtc_J2000(): void
    {
        $dt = JulianDay::toUtc(JulianDay::J2000);
        self::assertSame('2000', $dt->format('Y'));
        self::assertSame('01', $dt->format('m'));
        self::assertSame('01', $dt->format('d'));
        self::assertSame('12', $dt->format('H'));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function testJulianCenturies(): void
    {
        $t = JulianDay::julianCenturiesFromJ2000(JulianDay::J2000);
        self::assertEqualsWithDelta(0.0, $t, 1e-10);
    }

    public function testJulianCenturies_OneHundredYearsLater(): void
    {
        $jd = JulianDay::J2000 + JulianDay::DAYS_PER_CENTURY;
        $t  = JulianDay::julianCenturiesFromJ2000($jd);
        self::assertEqualsWithDelta(1.0, $t, 1e-10);
    }
}
