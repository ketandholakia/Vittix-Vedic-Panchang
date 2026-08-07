<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Panchanga;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\SolarMonth;
use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Tests for the PanchangService — the canonical Panchang entry point.
 */
final class PanchangServiceTest extends TestCase
{
    private PanchangService $service;

    protected function setUp(): void
    {
        $this->service = PanchangService::createDefault();
    }

    public function testGetForDateReturnsValidResult(): void
    {
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));
        $location = new GeoLocation(latitude: 23.0225, longitude: 72.5714);

        $result = $this->service->getForDate($date, $location);

        self::assertSame('2026-08-05', $result->date->format('Y-m-d'));
        self::assertNotNull($result->tithi);
        self::assertNotNull($result->nakshatra);
        self::assertNotNull($result->yoga);
        self::assertNotNull($result->karana);
        self::assertNotNull($result->vara);
        self::assertNotNull($result->solarMonth);
    }

    public function testGetForDateSolarEventsPresent(): void
    {
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));
        $location = new GeoLocation(latitude: 23.0225, longitude: 72.5714);

        $result = $this->service->getForDate($date, $location);

        self::assertTrue($result->solarEvents->hasSunrise());
        self::assertTrue($result->solarEvents->hasSunset());
    }

    public function testToJsonProducesValidJson(): void
    {
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));
        $location = new GeoLocation(latitude: 23.0225, longitude: 72.5714);

        $result = $this->service->getForDate($date, $location);
        $json = $result->toJson();

        $decoded = json_decode($json, true);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('date', $decoded);
        self::assertArrayHasKey('tithi', $decoded);
        self::assertArrayHasKey('nakshatra', $decoded);
        self::assertArrayHasKey('yoga', $decoded);
        self::assertArrayHasKey('karana', $decoded);
        self::assertArrayHasKey('vara', $decoded);
        self::assertArrayHasKey('samvat', $decoded);
        self::assertArrayHasKey('ayanamsha', $decoded);
    }

    public function testToArrayHasExpectedStructure(): void
    {
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));
        $location = new GeoLocation(latitude: 23.0225, longitude: 72.5714);

        $result = $this->service->getForDate($date, $location);
        $array = $result->toArray();

        self::assertArrayHasKey('sunrise', $array);
        self::assertArrayHasKey('sunset', $array);
        self::assertArrayHasKey('moonrise', $array);
        self::assertArrayHasKey('moonset', $array);
        self::assertArrayHasKey('solarMonth', $array);
        self::assertArrayHasKey('samvat', $array);
        self::assertArrayHasKey('vikram', $array['samvat']);
        self::assertArrayHasKey('shaka', $array['samvat']);
    }

    public function testGetAstronomyReportReturnsValidResult(): void
    {
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));
        $location = new GeoLocation(latitude: 23.0225, longitude: 72.5714);

        $result = $this->service->getAstronomyReport($date, $location);

        self::assertGreaterThan(0.0, $result->ayanamsha);
        self::assertGreaterThanOrEqual(0.0, $result->siderealTime);
        self::assertLessThan(360.0, $result->siderealTime);
        self::assertNotNull($result->solarMonth);
        self::assertInstanceOf(SolarMonth::class, $result->solarMonth);
    }

    public function testAstronomyReportJsonIsValid(): void
    {
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));
        $location = new GeoLocation(latitude: 23.0225, longitude: 72.5714);

        $result = $this->service->getAstronomyReport($date, $location);
        $json = $result->toJson();

        $decoded = json_decode($json, true);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('solar', $decoded);
        self::assertArrayHasKey('lunar', $decoded);
        self::assertArrayHasKey('ayanamsha', $decoded);
        self::assertArrayHasKey('siderealTime', $decoded);
        self::assertArrayHasKey('solarMonth', $decoded);
    }
}
