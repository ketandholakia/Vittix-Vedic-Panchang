<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\Enum\Tithi;

final class LimbBoundaryPropertyTest extends TestCase
{
    public function testTithiMonotonicityOverPeriod(): void
    {
        $startDate = new DateTimeImmutable('2024-01-01', new DateTimeZone('Asia/Kolkata'));
        $location = new GeoLocation(28.6139, 77.2090, 200); // New Delhi
        $service = PanchangService::createDefault();

        $previousTithiIndex = null;
        
        // Test over a sequence of 60 consecutive days
        for ($i = 0; $i < 60; $i++) {
            $date = $startDate->modify("+$i days");
            $result = $service->getForDate($date, $location);

            $currentTithiIndex = $result->tithi->value;

            if ($previousTithiIndex !== null) {
                // Tithi should either:
                // 1. Be the same as yesterday (Adhika Tithi)
                // 2. Increment by 1 (Normal)
                // 3. Increment by 2 (Kshaya Tithi - one skipped)
                // 4. Wrap around from 30 to 1
                
                $diff = $currentTithiIndex - $previousTithiIndex;

                if ($previousTithiIndex === 29) {
                    self::assertContains($currentTithiIndex, [29, 0, 1], "Invalid transition from Tithi 29 to {$currentTithiIndex}");
                } else if ($previousTithiIndex === 28 && $currentTithiIndex === 0) {
                    // Valid Kshaya Tithi skipping 29
                    self::assertLessThan(0, $diff, 'Kshaya Tithi must decrease the index (28 -> 0)');
                } else {
                    self::assertGreaterThanOrEqual(0, $diff, "Tithi index should not decrease normally (was {$previousTithiIndex}, now {$currentTithiIndex})");
                    self::assertLessThanOrEqual(2, $diff, "Tithi index skipped more than one day (was {$previousTithiIndex}, now {$currentTithiIndex})");
                }
            }

            // Also check that if there is a second Tithi in the same day, it's exactly the next one
            if ($result->tithiSecond !== null) {
                $expectedSecondIndex = $currentTithiIndex === 29 ? 0 : $currentTithiIndex + 1;
                self::assertSame($expectedSecondIndex, $result->tithiSecond->value, "Second tithi must strictly follow the first tithi");
            }

            $previousTithiIndex = $result->tithiSecond !== null ? $result->tithiSecond->value : $currentTithiIndex;
        }
    }
}
