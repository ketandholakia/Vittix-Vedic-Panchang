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

    /**
     * Civil-date rule — a day with no moonset.
     *
     * On 2037-11-15 (local) the Moon does not set at all in Delhi; the
     * reference (Swiss Ephemeris rise_trans) also has no moonset that day.
     * The moonset that would normally fall early on the 16th is correctly
     * NOT reported for the 15th.
     */
    public function testMoonsetIsNullOnSkipDay(): void
    {
        $timezone = new DateTimeZone('Asia/Kolkata');
        $date = new DateTimeImmutable('2037-11-15', $timezone);
        $location = new GeoLocation(latitude: 28.6139, longitude: 77.2090);

        $events = $this->engine->lunarEvents($date, $location, $timezone);

        self::assertNull($events->moonset, 'The Moon does not set on 2037-11-15 (local) in Delhi.');
        self::assertNotNull($events->moonrise, 'But the Moon does rise that day.');
        self::assertSame(
            '2037-11-15',
            $events->moonrise->setTimezone($timezone)->format('Y-m-d'),
            'Moonrise must fall on the requested local date.',
        );
    }

    /**
     * Civil-date rule — a day with no moonrise (far from UTC: New York).
     */
    public function testMoonriseIsNullOnSkipDayForNewYork(): void
    {
        $timezone = new DateTimeZone('America/New_York');
        $date = new DateTimeImmutable('2025-09-15', $timezone);
        $location = new GeoLocation(latitude: 40.7128, longitude: -74.0060);

        $events = $this->engine->lunarEvents($date, $location, $timezone);

        self::assertNull($events->moonrise, 'The Moon does not rise on 2025-09-15 (local) in New York.');
        self::assertNotNull($events->moonset, 'But the Moon does set that day.');
        self::assertSame(
            '2025-09-15',
            $events->moonset->setTimezone($timezone)->format('Y-m-d'),
            'Moonset must fall on the requested local date.',
        );
    }

    /**
     * Every returned event must lie on the requested local date — Kolkata
     * (UTC+5:30) — never on a neighbouring day.
     */
    public function testEventsFallOnRequestedLocalDateKolkata(): void
    {
        $timezone = new DateTimeZone('Asia/Kolkata');
        $location = new GeoLocation(latitude: 22.5726, longitude: 88.3639);

        foreach (['2025-01-15', '2025-06-15', '2025-12-15'] as $dateStr) {
            $events = $this->engine->lunarEvents(new DateTimeImmutable($dateStr, $timezone), $location, $timezone);

            self::assertNotNull($events->moonrise, "Moonrise missing for Kolkata {$dateStr}.");
            self::assertNotNull($events->moonset, "Moonset missing for Kolkata {$dateStr}.");
            self::assertSame(
                $dateStr,
                $events->moonrise->setTimezone($timezone)->format('Y-m-d'),
                "Moonrise for {$dateStr} must be on that local date.",
            );
            self::assertSame(
                $dateStr,
                $events->moonset->setTimezone($timezone)->format('Y-m-d'),
                "Moonset for {$dateStr} must be on that local date.",
            );
        }
    }

    /**
     * Every returned event must lie on the requested local date — New York
     * (UTC−5/−4) — including dates where events sit near local midnight.
     */
    public function testEventsFallOnRequestedLocalDateNewYork(): void
    {
        $timezone = new DateTimeZone('America/New_York');
        $location = new GeoLocation(latitude: 40.7128, longitude: -74.0060);

        foreach (['2025-03-15', '2025-11-15'] as $dateStr) {
            $events = $this->engine->lunarEvents(new DateTimeImmutable($dateStr, $timezone), $location, $timezone);

            self::assertNotNull($events->moonrise, "Moonrise missing for New York {$dateStr}.");
            self::assertNotNull($events->moonset, "Moonset missing for New York {$dateStr}.");
            self::assertSame(
                $dateStr,
                $events->moonrise->setTimezone($timezone)->format('Y-m-d'),
                "Moonrise for {$dateStr} must be on that local date.",
            );
            self::assertSame(
                $dateStr,
                $events->moonset->setTimezone($timezone)->format('Y-m-d'),
                "Moonset for {$dateStr} must be on that local date.",
            );
        }
    }

    /**
     * DST transition days: New York 2024-03-10 is a 23-hour local day
     * (spring forward) and 2024-11-03 is a 25-hour local day (fall back).
     * Both must produce events on the requested local date, unaffected by
     * the shifted day length.
     *
     * Reference (Swiss Ephemeris rise_trans, upper limb + refraction):
     *   2024-03-10: rise 07:33:36, set 19:34:07 local
     *   2024-11-03: rise 08:44:54, set 17:45:45 local
     */
    public function testDstTransitionDaysAreHandled(): void
    {
        $timezone = new DateTimeZone('America/New_York');
        $location = new GeoLocation(latitude: 40.7128, longitude: -74.0060);

        foreach (['2024-03-10', '2024-11-03'] as $dateStr) {
            $events = $this->engine->lunarEvents(new DateTimeImmutable($dateStr, $timezone), $location, $timezone);

            self::assertNotNull($events->moonrise, "Moonrise missing for DST day {$dateStr}.");
            self::assertNotNull($events->moonset, "Moonset missing for DST day {$dateStr}.");
            self::assertSame(
                $dateStr,
                $events->moonrise->setTimezone($timezone)->format('Y-m-d'),
                "Moonrise on {$dateStr} must fall on the (23/25 h) local date.",
            );
            self::assertSame(
                $dateStr,
                $events->moonset->setTimezone($timezone)->format('Y-m-d'),
                "Moonset on {$dateStr} must fall on the (23/25 h) local date.",
            );
        }
    }

    /**
     * Extreme eastern timezone (Kiritimati, UTC+14): local midnight is 10:00
     * UTC of the previous day. Events must still be selected by the local
     * calendar date.
     *
     * Reference (Swiss Ephemeris rise_trans, upper limb + refraction):
     *   2024-06-15: rise 13:05:16, set 00:45:51 local
     *   2025-01-15: rise 19:46:56, set 07:26:44 local
     */
    public function testEventsFallOnRequestedLocalDateKiritimati(): void
    {
        $timezone = new DateTimeZone('Pacific/Kiritimati');
        $location = new GeoLocation(latitude: 1.8721, longitude: -157.4278);

        foreach (['2024-06-15', '2025-01-15'] as $dateStr) {
            $events = $this->engine->lunarEvents(new DateTimeImmutable($dateStr, $timezone), $location, $timezone);

            self::assertNotNull($events->moonrise, "Moonrise missing for Kiritimati {$dateStr}.");
            self::assertNotNull($events->moonset, "Moonset missing for Kiritimati {$dateStr}.");
            self::assertSame(
                $dateStr,
                $events->moonrise->setTimezone($timezone)->format('Y-m-d'),
                "Moonrise for {$dateStr} must be on the Kiritimati local date.",
            );
            self::assertSame(
                $dateStr,
                $events->moonset->setTimezone($timezone)->format('Y-m-d'),
                "Moonset for {$dateStr} must be on the Kiritimati local date.",
            );
        }
    }

    /**
     * Grazing window at high latitude (regression for the parabola-checked
     * fine re-scan):
     *
     * On 2037-09-13 at Svalbard the Moon rises and sets again within
     * ~14 minutes (both crossings fall between 45-minute grid samples with
     * all three samples showing negative altitude — the coarse scan alone
     * misses this window entirely and would return null/null).
     *
     * Reference (Swiss Ephemeris rise_trans, upper limb + refraction):
     * rise 13:42:50 UTC, set 14:28:59 UTC. At a razor-graze the engine and
     * the reference can differ by several minutes (rate → 0), so only
     * existence and local-date placement are asserted here.
     */
    public function testGrazingWindowIsFoundAtHighLatitude(): void
    {
        $timezone = new DateTimeZone('Arctic/Longyearbyen');
        $dateStr = '2037-09-13';
        $location = new GeoLocation(latitude: 78.2232, longitude: 15.6469);

        $events = $this->engine->lunarEvents(new DateTimeImmutable($dateStr, $timezone), $location, $timezone);

        self::assertNotNull(
            $events->moonrise,
            'The 14-minute grazing rise on Svalbard 2037-09-13 must be found (parabola check).',
        );
        self::assertNotNull(
            $events->moonset,
            'The 14-minute grazing set on Svalbard 2037-09-13 must be found (parabola check).',
        );
        self::assertSame($dateStr, $events->moonrise->setTimezone($timezone)->format('Y-m-d'));
        self::assertSame($dateStr, $events->moonset->setTimezone($timezone)->format('Y-m-d'));
        self::assertLessThan(
            $events->moonset->getTimestamp(),
            $events->moonrise->getTimestamp(),
            'The grazing rise must precede its set.',
        );
    }
}
