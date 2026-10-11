<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Panchanga;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\SolarMonth;
use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Tests for extreme edge cases to ensure the library handles complex
 * chronological or geographical conditions without crashing.
 */
final class EdgeCaseTest extends TestCase
{
    private PanchangService $service;

    protected function setUp(): void
    {
        $this->service = PanchangService::createDefault();
    }

    /**
     * Extreme latitudes (Arctic Circle or above) where the Sun might not rise or set.
     */
    public function testExtremeLatitudeSvalbardNoSunriseMidsummer(): void
    {
        // Svalbard, Norway (Lat 78.22, Lon 15.65)
        $date = new DateTimeImmutable('2026-06-21', new DateTimeZone('Arctic/Longyearbyen'));
        $location = new GeoLocation(78.2232, 15.6469);

        // This shouldn't crash. Since it's midsummer, the sun doesn't set.
        $result = $this->service->getForDate($date, $location);

        // Solar events may be null or represent transit times depending on the algorithm's fallback.
        // Primarily, we are testing for crash-free execution with a sane result date.
        self::assertSame('2026-06-21', $result->date->format('Y-m-d'));
    }

    public function testExtremeLatitudeSvalbardNoSunriseMidwinter(): void
    {
        // Midwinter in Svalbard - Sun never rises
        $date = new DateTimeImmutable('2026-12-21', new DateTimeZone('Arctic/Longyearbyen'));
        $location = new GeoLocation(78.2232, 15.6469);

        $result = $this->service->getForDate($date, $location);

        self::assertSame('2026-12-21', $result->date->format('Y-m-d'));
    }

    /**
     * Daylight Saving Time transition boundaries.
     */
    public function testDstSpringForwardGapTimeDoesNotCrash(): void
    {
        // In US Eastern time, 2024-03-10 02:00:00 skipped to 03:00:00
        // We will pass 02:30:00. PHP typically shifts this to 03:30 automatically, 
        // but we ensure the Panchang engine handles the date.
        $date = new DateTimeImmutable('2024-03-10 02:30:00', new DateTimeZone('America/New_York'));
        $location = new GeoLocation(40.7128, -74.0060);

        $result = $this->service->getForDate($date, $location);

        self::assertSame('2024-03-10', $result->date->format('Y-m-d'));
    }

    public function testDstFallBackOverlapDoesNotCrash(): void
    {
        // In US Eastern time, 2024-11-03 01:00:00 is repeated.
        $date = new DateTimeImmutable('2024-11-03 01:30:00', new DateTimeZone('America/New_York'));
        $location = new GeoLocation(40.7128, -74.0060);

        $result = $this->service->getForDate($date, $location);

        self::assertSame('2024-11-03', $result->date->format('Y-m-d'));
    }

    /**
     * Historical Date: Ensure Ayana calculation doesn't crash on negative Julian Centuries.
     */
    public function testAncientDateBeforeJ2000(): void
    {
        $date = new DateTimeImmutable('1857-05-10', new DateTimeZone('Asia/Kolkata')); // First War of Independence
        $location = new GeoLocation(28.9845, 77.7064); // Meerut

        $result = $this->service->getForDate($date, $location);

        self::assertSame('1857-05-10', $result->date->format('Y-m-d'));
        self::assertInstanceOf(SolarMonth::class, $result->solarMonth);
    }

    /**
     * Far Future Date: Ensure no overflow for dates thousands of years in the future.
     */
    public function testFarFutureDate(): void
    {
        $date = new DateTimeImmutable('5000-01-01', new DateTimeZone('UTC'));
        $location = new GeoLocation(0.0, 0.0);

        $result = $this->service->getForDate($date, $location);

        self::assertSame('5000-01-01', $result->date->format('Y-m-d'));
    }
}
