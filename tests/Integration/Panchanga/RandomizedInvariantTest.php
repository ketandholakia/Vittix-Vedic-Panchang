<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Panchanga;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\ValueObject\GeoLocation;

final class RandomizedInvariantTest extends TestCase
{
    private Panchang $panchang;
    private GeoLocation $location;

    protected function setUp(): void
    {
        $this->panchang = Panchang::createDefault();
        // New Delhi roughly
        $this->location = new GeoLocation(28.6139, 77.2090, 200.0);
    }

    public function testInvariantsOverRandomDates(): void
    {
        // Set a fixed seed so tests are reproducible but check many cases
        mt_srand(42);
        
        $timezone = new DateTimeZone('Asia/Kolkata');
        
        for ($i = 0; $i < 50; $i++) {
            // Random year between 1900 and 2100
            $year = mt_rand(1900, 2100);
            $month = mt_rand(1, 12);
            $day = mt_rand(1, 28);
            
            $dt = (new DateTimeImmutable(sprintf('%04d-%02d-%02d 12:00:00', $year, $month, $day)))
                ->setTimezone($timezone);
                
            $result = $this->panchang->day($dt, $this->location);
            
            // Invariant 1: Sunrise is before Sunset
            if ($result->solarEvents->sunrise !== null && $result->solarEvents->sunset !== null) {
                self::assertLessThan(
                    $result->solarEvents->sunset->getTimestamp(),
                    $result->solarEvents->sunrise->getTimestamp(),
                    "Sunrise should be before Sunset on $year-$month-$day"
                );
            }
            
            // Invariant 2: Tithi at sunrise matches English name enum logic
            self::assertNotEmpty($result->tithiAtSunrise->nameEnglish());
            
            // Invariant 3: End times (if present) are after sunrise (or very close to it)
            if ($result->tithiEndsAt !== null && $result->solarEvents->sunrise !== null) {
                // Technically it could end exactly AT sunrise in our boundary test, so we use >= or allow a tiny float epsilon
                // Tithi might end before sunrise if it's the PREVIOUS day's tithi, but forDay() returns the one prevailing AT sunrise
                // which ends AFTER sunrise.
                self::assertGreaterThanOrEqual(
                    $result->solarEvents->sunrise->getTimestamp() - 5,
                    $result->tithiEndsAt->getTimestamp(),
                    "Tithi end time should be after sunrise on $year-$month-$day"
                );
            }
        }
    }
}
