<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\ValueObject\GeoLocation;

final class MultiLocationOracleTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: GeoLocation, 2: string}>
     */
    public static function locationProvider(): array
    {
        return [
            'New York' => ['America/New_York', new GeoLocation(40.7128, -74.0060, 10), '2024-03-01'],
            'Tokyo' => ['Asia/Tokyo', new GeoLocation(35.6762, 139.6503, 40), '2024-03-01'],
            'Sydney' => ['Australia/Sydney', new GeoLocation(-33.8688, 151.2093, 3), '2024-03-01'],
            'London' => ['Europe/London', new GeoLocation(51.5074, -0.1278, 35), '2024-03-01'],
            'Reykjavik (High Lat)' => ['Atlantic/Reykjavik', new GeoLocation(64.1466, -21.9426, 8), '2024-03-01'],
            'Nairobi (Equator)' => ['Africa/Nairobi', new GeoLocation(-1.2921, 36.8219, 1795), '2024-03-01'],
            'Mount Everest (High Elev)' => ['Asia/Katmandu', new GeoLocation(27.9881, 86.9250, 8848), '2024-03-01'],
            'Punta Arenas (Deep South)' => ['America/Punta_Arenas', new GeoLocation(-53.1500, -70.9167, 4), '2024-03-01'],
            'Mumbai' => ['Asia/Kolkata', new GeoLocation(19.0760, 72.8777, 14), '2024-03-01'],
            'Los Angeles' => ['America/Los_Angeles', new GeoLocation(34.0522, -118.2437, 71), '2024-03-01'],
        ];
    }

    /**
     * @dataProvider locationProvider
     */
    public function testPanchangRunsWithoutErrorsAcrossExtremes(string $tzName, GeoLocation $location, string $dateStr): void
    {
        $panchang = Panchang::createDefault();
        $date = new DateTimeImmutable($dateStr, new DateTimeZone($tzName));

        // Attempt to calculate the full panchang day.
        $day = $panchang->day($date, $location);

        // Verify some basic sanity checks (that it returned valid objects)
        self::assertNotNull($day->tithiAtSunrise);
        self::assertNotNull($day->nakshatraAtSunrise);
        
        // Assert sunrise and sunset might be null for extreme latitudes during certain times of year,
        // but they should be defined for most of our test cases (March 1 is close to equinox, so all should have them).
        self::assertNotNull($day->solarEvents->sunrise, 'Sunrise should not be null near equinox');
        self::assertNotNull($day->solarEvents->sunset, 'Sunset should not be null near equinox');
    }

    public function testRelativeTimeCorrectnessAcrossLocations(): void
    {
        $panchang = Panchang::createDefault();
        
        // Use the same UTC instant represented in different timezones
        $dateStr = '2024-03-01';

        $tokyoLoc = new GeoLocation(35.6762, 139.6503, 40);
        $tokyoDate = new DateTimeImmutable($dateStr, new DateTimeZone('Asia/Tokyo'));
        $tokyoDay = $panchang->day($tokyoDate, $tokyoLoc);

        $nyLoc = new GeoLocation(40.7128, -74.0060, 10);
        $nyDate = new DateTimeImmutable($dateStr, new DateTimeZone('America/New_York'));
        $nyDay = $panchang->day($nyDate, $nyLoc);

        self::assertNotNull($tokyoDay->solarEvents->sunrise);
        self::assertNotNull($nyDay->solarEvents->sunrise);

        // Tokyo sunrise on March 1st should happen before New York sunrise on March 1st in absolute UTC time
        self::assertLessThan(
            $nyDay->solarEvents->sunrise->getTimestamp(),
            $tokyoDay->solarEvents->sunrise->getTimestamp(),
            'Sunrise in Tokyo should occur earlier in absolute time than sunrise in New York'
        );
    }
}
