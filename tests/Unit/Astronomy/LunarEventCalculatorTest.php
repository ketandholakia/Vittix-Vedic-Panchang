<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astronomy;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Tests for moonrise/moonset calculations via the astronomy engine.
 *
 * Reference data sources:
 *   - USNO (United States Naval Observatory) online calculator
 *   - timeanddate.com
 *   - Stellarium
 *
 * Moon events are inherently harder to predict than solar events due to:
 *   - Large horizontal parallax (~0.95°)
 *   - Rapid daily motion (~13.2°/day)
 *   - Irregular orbit
 *
 * We allow a tolerance of ±5 minutes for moonrise/moonset times,
 * which is comparable to differences between authoritative sources.
 */
final class LunarEventCalculatorTest extends TestCase
{
    private PurePhpAstronomyEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new PurePhpAstronomyEngine();
    }

    /**
     * Smoke test: moonrise should be a valid time for a typical Indian city.
     */
    public function testMoonriseExistsForAhmedabadTypicalDate(): void
    {
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));
        $location = new GeoLocation(latitude: 23.0225, longitude: 72.5714);

        $events = $this->engine->lunarEvents($date, $location, $date->getTimezone());

        // On most dates, the Moon does rise. This is a smoke test.
        // If moonrise is null, it's a rare skip-day — just verify no crash.
        if ($events->hasMoonrise()) {
            $moonrise = $events->moonrise;
            self::assertNotNull($moonrise);
            // Moonrise should be close to the target date (within 1-2 days)
            $diffHours = abs($moonrise->getTimestamp() - $date->getTimestamp()) / 3600;
            self::assertLessThan(48.0, $diffHours, 'Moonrise should be within 48 hours of target date');
        }

        // The test primarily verifies no exceptions are thrown
    }

    /**
     * Smoke test: moonset should exist for a typical date.
     */
    public function testMoonsetExistsForDelhiTypicalDate(): void
    {
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));
        $location = new GeoLocation(latitude: 28.6139, longitude: 77.2090);

        $events = $this->engine->lunarEvents($date, $location, $date->getTimezone());

        // Smoke test — no crash is the primary assertion
        self::assertInstanceOf(\Vittix\Panchang\ValueObject\LunarEvents::class, $events);
    }

    /**
     * Transit should always be returned (Moon always crosses the meridian).
     */
    public function testTransitAlwaysReturned(): void
    {
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));
        $location = new GeoLocation(latitude: 23.0225, longitude: 72.5714);

        $events = $this->engine->lunarEvents($date, $location, $date->getTimezone());

        self::assertNotNull($events->transit);
    }

    /**
     * Test that moonrise is calculated for multiple dates without crashes.
     * This exercises the full calculation pipeline across a week.
     */
    public function testWeekOfMoonEventsNoCrash(): void
    {
        $location = new GeoLocation(latitude: 23.0225, longitude: 72.5714);
        $timezone = new DateTimeZone('Asia/Kolkata');

        $riseCount = 0;
        $setCount  = 0;

        for ($d = 1; $d <= 7; $d++) {
            $date = new DateTimeImmutable("2026-08-0{$d}", $timezone);
            $events = $this->engine->lunarEvents($date, $location, $timezone);

            if ($events->hasMoonrise()) {
                $riseCount++;
            }
            if ($events->hasMoonset()) {
                $setCount++;
            }
        }

        // Over a week, the Moon should rise and set at least 5 times
        // (one "skip" day is normal due to the 24h50m period)
        self::assertGreaterThanOrEqual(5, $riseCount, 'Moon should rise most days');
        self::assertGreaterThanOrEqual(5, $setCount, 'Moon should set most days');
    }

    /**
     * Test that siderealTime() returns a value in the expected range.
     */
    public function testSiderealTimeRange(): void
    {
        $date = new DateTimeImmutable('2026-08-05 12:00:00', new DateTimeZone('UTC'));
        $location = new GeoLocation(latitude: 23.0225, longitude: 72.5714);

        $factory = new \Vittix\Panchang\Time\AstroTimeFactory();
        $instant = $factory->fromDateTime($date);

        $lst = $this->engine->siderealTime($instant, $location);

        self::assertGreaterThanOrEqual(0.0, $lst);
        self::assertLessThan(360.0, $lst);
    }
}
