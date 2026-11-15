<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Muhurta;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Muhurta\MuhurtaCalculator;

/**
 * All 14 Choghadiya rows (7 weekdays × day/night) derived structurally.
 *
 * Cycle: Udveg, Chal, Labh, Amrit, Kaal, Shubh, Rog.
 *   • Day row: starts at the weekday lord's offset (Sun 0, Mon 3, Tue 6,
 *     Wed 2, Thu 5, Fri 1, Sat 4), +1 per segment.
 *   • Night row: starts at (dayStart + 5) mod 7, −2 per segment.
 */
final class MuhurtaCalculatorChoghadiyaTest extends TestCase
{
    private MuhurtaCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new MuhurtaCalculator();
    }

    /**
     * @return list<string>
     */
    private function expectedRow(int $weekdayIndex, bool $isDay): array
    {
        $cycle = ['Udveg', 'Chal', 'Labh', 'Amrit', 'Kaal', 'Shubh', 'Rog'];
        $dayStarts = [0, 3, 6, 2, 5, 1, 4]; // Sun, Mon, Tue, Wed, Thu, Fri, Sat

        $dayStart = $dayStarts[$weekdayIndex];
        $row = [];

        if ($isDay) {
            for ($i = 0; $i < 8; $i++) {
                $row[] = $cycle[($dayStart + $i) % 7];
            }

            return $row;
        }

        $nightStart = ($dayStart + 5) % 7;
        for ($i = 0; $i < 8; $i++) {
            $row[] = $cycle[(($nightStart - 2 * $i) % 7 + 7) % 7];
        }

        return $row;
    }

    public function testAllFourteenRowsMatchStructuralDerivation(): void
    {
        $sunrise = new DateTimeImmutable('2026-06-15 06:00:00', new DateTimeZone('Asia/Kolkata'));
        $sunset = new DateTimeImmutable('2026-06-15 19:10:00', new DateTimeZone('Asia/Kolkata'));
        $nextSunrise = new DateTimeImmutable('2026-06-16 06:00:00', new DateTimeZone('Asia/Kolkata'));

        $weekdayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        for ($weekday = 0; $weekday < 7; $weekday++) {
            foreach ([true, false] as $isDay) {
                $windows = $isDay
                    ? $this->calculator->getDayChoghadiya($sunrise, $sunset, $weekday)
                    : $this->calculator->getNightChoghadiya($sunset, $nextSunrise, $weekday);

                $names = array_map(static fn ($w) => $w->name, $windows);

                self::assertSame(
                    $this->expectedRow($weekday, $isDay),
                    $names,
                    sprintf('%s %s Choghadiya row', $weekdayNames[$weekday], $isDay ? 'day' : 'night')
                );
            }
        }
    }

    public function testTuesdayAndWednesdayNightRows(): void
    {
        // Regression: these two rows were wrong in the hardcoded tables.
        $sunrise = new DateTimeImmutable('2026-06-15 06:00:00', new DateTimeZone('Asia/Kolkata'));
        $sunset = new DateTimeImmutable('2026-06-15 19:10:00', new DateTimeZone('Asia/Kolkata'));
        $nextSunrise = new DateTimeImmutable('2026-06-16 06:00:00', new DateTimeZone('Asia/Kolkata'));

        $tuesdayNight = array_map(
            static fn ($w) => $w->name,
            $this->calculator->getNightChoghadiya($sunset, $nextSunrise, 2)
        );
        self::assertSame(
            ['Kaal', 'Labh', 'Udveg', 'Shubh', 'Amrit', 'Chal', 'Rog', 'Kaal'],
            $tuesdayNight
        );

        $wednesdayNight = array_map(
            static fn ($w) => $w->name,
            $this->calculator->getNightChoghadiya($sunset, $nextSunrise, 3)
        );
        self::assertSame(
            ['Udveg', 'Shubh', 'Amrit', 'Chal', 'Rog', 'Kaal', 'Labh', 'Udveg'],
            $wednesdayNight
        );
    }
}
