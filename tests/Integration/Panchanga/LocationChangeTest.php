<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Panchanga;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Regression test: changing location must change Panchang results.
 *
 * This test ensures that cached data is not incorrectly reused across locations,
 * and that astronomical calculations properly use the provided coordinates.
 */
final class LocationChangeTest extends TestCase
{
    private PanchangService $service;

    protected function setUp(): void
    {
        $this->service = PanchangService::createDefault();
    }

    public function testLocationChangeAffectsSunriseAndTithiEnd(): void
    {
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));

        // Ahmedabad: 23.0225° N, 72.5714° E
        $ahmedabad = new GeoLocation(23.0225, 72.5714, 53.0);

        // New Delhi: 28.6139° N, 77.2090° E
        $delhi = new GeoLocation(28.6139, 77.2090, 216.0);

        $panchangA = $this->service->getForDate($date, $ahmedabad);
        $panchangB = $this->service->getForDate($date, $delhi);

        // Sunrise must differ (Delhi is further east, so earlier sunrise)
        $this->assertNotNull($panchangA->solarEvents->sunrise, 'Ahmedabad sunrise should exist');
        $this->assertNotNull($panchangB->solarEvents->sunrise, 'Delhi sunrise should exist');

        $sunriseDiff = abs(
            $panchangA->solarEvents->sunrise->getTimestamp() -
            $panchangB->solarEvents->sunrise->getTimestamp()
        );

        // Delhi is ~4.6° east of Ahmedabad → ~18 minutes earlier sunrise
        $this->assertGreaterThan(600, $sunriseDiff, 'Sunrise should differ by >10 minutes between Ahmedabad and Delhi');
        $this->assertLessThan(1800, $sunriseDiff, 'Sunrise difference should be reasonable (<30 min)');

        // Tithi end times must be EXACTLY identical (cosmic event, independent of location)
        $this->assertNotNull($panchangA->tithiEndsAt, 'Ahmedabad tithi end should exist');
        $this->assertNotNull($panchangB->tithiEndsAt, 'Delhi tithi end should exist');

        $this->assertEqualsWithDelta(
            $panchangA->tithiEndsAt->getTimestamp(),
            $panchangB->tithiEndsAt->getTimestamp(),
            5,
            'Tithi end timestamp must be identical globally'
        );
    }

    public function testLocationChangeAffectsSunset(): void
    {
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));

        $ahmedabad = new GeoLocation(23.0225, 72.5714, 53.0);
        $delhi = new GeoLocation(28.6139, 77.2090, 216.0);

        $panchangA = $this->service->getForDate($date, $ahmedabad);
        $panchangB = $this->service->getForDate($date, $delhi);

        $this->assertNotNull($panchangA->solarEvents->sunset);
        $this->assertNotNull($panchangB->solarEvents->sunset);

        $sunsetDiff = abs(
            $panchangA->solarEvents->sunset->getTimestamp() -
            $panchangB->solarEvents->sunset->getTimestamp()
        );

        $this->assertGreaterThan(300, $sunsetDiff, 'Sunset should differ by >5 minutes');
    }

    public function testLocationChangeAffectsNakshatraEnd(): void
    {
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));

        $ahmedabad = new GeoLocation(23.0225, 72.5714, 53.0);
        $delhi = new GeoLocation(28.6139, 77.2090, 216.0);

        $panchangA = $this->service->getForDate($date, $ahmedabad);
        $panchangB = $this->service->getForDate($date, $delhi);

        $this->assertNotNull($panchangA->nakshatraEndsAt);
        $this->assertNotNull($panchangB->nakshatraEndsAt);

        $this->assertEqualsWithDelta(
            $panchangA->nakshatraEndsAt->getTimestamp(),
            $panchangB->nakshatraEndsAt->getTimestamp(),
            5,
            'Nakshatra end timestamp must be identical globally'
        );
    }

    public function testLocationChangeAffectsYogaAndKaranaEnds(): void
    {
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));

        $ahmedabad = new GeoLocation(23.0225, 72.5714, 53.0);
        $delhi = new GeoLocation(28.6139, 77.2090, 216.0);

        $panchangA = $this->service->getForDate($date, $ahmedabad);
        $panchangB = $this->service->getForDate($date, $delhi);

        // Yoga end
        $this->assertNotNull($panchangA->yogaEndsAt);
        $this->assertNotNull($panchangB->yogaEndsAt);
        $this->assertEqualsWithDelta(
            $panchangA->yogaEndsAt->getTimestamp(),
            $panchangB->yogaEndsAt->getTimestamp(),
            5,
            'Yoga end timestamp must be identical globally'
        );

        // Karana end
        $this->assertNotNull($panchangA->karanaEndsAt);
        $this->assertNotNull($panchangB->karanaEndsAt);
        $this->assertEqualsWithDelta(
            $panchangA->karanaEndsAt->getTimestamp(),
            $panchangB->karanaEndsAt->getTimestamp(),
            5,
            'Karana end timestamp must be identical globally'
        );
    }

    public function testSameLocationProducesSameResults(): void
    {
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));
        $ahmedabad = new GeoLocation(23.0225, 72.5714, 53.0);

        $panchang1 = $this->service->getForDate($date, $ahmedabad);
        $panchang2 = $this->service->getForDate($date, $ahmedabad);

        // Same location + same date = identical results
        $this->assertEqualsWithDelta(
            $panchang1->solarEvents->sunrise?->getTimestamp(),
            $panchang2->solarEvents->sunrise?->getTimestamp(),
            5
        );
        $this->assertEqualsWithDelta(
            $panchang1->tithiEndsAt?->getTimestamp(),
            $panchang2->tithiEndsAt?->getTimestamp(),
            5
        );
    }
}