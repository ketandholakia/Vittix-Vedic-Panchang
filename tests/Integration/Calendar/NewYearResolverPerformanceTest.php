<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\Tests\Support\TestLocations;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Regression: the Samvat new-year resolution used to trigger whole-year
 * festival scans (1.1–1.7 s for the first getForDate() at a new location).
 * It must resolve from the lunation walk + a 4-day window instead.
 */
final class NewYearResolverPerformanceTest extends TestCase
{
    public function testFirstGetForDateForNewLocationIsFast(): void
    {
        $service = PanchangService::createDefault();

        // A location no other test in this process has used.
        $tokyo = new GeoLocation(35.6762, 139.6503, 40.0);
        $date = new DateTimeImmutable('2026-06-15 12:00', new DateTimeZone('Asia/Tokyo'));

        $start = microtime(true);
        $service->getForDate($date, $tokyo);
        $elapsed = microtime(true) - $start;

        self::assertLessThan(
            0.25,
            $elapsed,
            sprintf('first getForDate() for a new location took %.3fs (must be < 0.25s)', $elapsed)
        );
    }

    public function testFirstGetForDateForNewYearAndLocationIsFast(): void
    {
        $service = PanchangService::createDefault();

        // Fresh year AND location: no cached scan can be reused.
        $location = new GeoLocation(-33.8688, 151.2093, 58.0); // Sydney
        $date = new DateTimeImmutable('2031-06-15 12:00', new DateTimeZone('Australia/Sydney'));

        $start = microtime(true);
        $service->getForDate($date, $location);
        $elapsed = microtime(true) - $start;

        self::assertLessThan(0.25, $elapsed, sprintf('took %.3fs', $elapsed));
    }
}
