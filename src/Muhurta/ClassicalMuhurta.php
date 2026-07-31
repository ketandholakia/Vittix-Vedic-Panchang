<?php

declare(strict_types=1);

namespace Vittix\Panchang\Muhurta;

use DateTimeImmutable;
use Vittix\Panchang\ValueObject\TimeWindow;

final class ClassicalMuhurta
{
    public function __construct(
        private readonly MuhurtaCalculator $calculator
    ) {
    }

    /**
     * Calculates Abhijit Muhurta (the 8th Muhurta of the day).
     */
    public function getAbhijit(DateTimeImmutable $sunrise, DateTimeImmutable $sunset): TimeWindow
    {
        $muhurtas = $this->calculator->getDayMuhurtas($sunrise, $sunset);
        // 8th muhurta is index 7
        return $muhurtas[7];
    }

    /**
     * Calculates Brahma Muhurta (the 14th Muhurta of the night).
     */
    public function getBrahmaMuhurta(DateTimeImmutable $sunset, DateTimeImmutable $nextSunrise): TimeWindow
    {
        $muhurtas = $this->calculator->getNightMuhurtas($sunset, $nextSunrise);
        // 14th muhurta is index 13
        return $muhurtas[13];
    }

    /**
     * Calculates Rahu Kaal for the day.
     * The day (sunrise to sunset) is divided into 8 equal parts.
     * The segment assigned to Rahu depends on the weekday.
     */
    public function getRahuKaal(DateTimeImmutable $sunrise, DateTimeImmutable $sunset, int $weekdayIndex): TimeWindow
    {
        return $this->getKarmakalaSegment($sunrise, $sunset, $weekdayIndex, [
            0 => 8, // Sunday: 8th segment
            1 => 2, // Monday: 2nd segment
            2 => 7, // Tuesday: 7th segment
            3 => 5, // Wednesday: 5th segment
            4 => 6, // Thursday: 6th segment
            5 => 4, // Friday: 4th segment
            6 => 3, // Saturday: 3rd segment
        ], 'Rahu Kaal');
    }

    /**
     * Calculates Yamaganda for the day.
     */
    public function getYamaganda(DateTimeImmutable $sunrise, DateTimeImmutable $sunset, int $weekdayIndex): TimeWindow
    {
        return $this->getKarmakalaSegment($sunrise, $sunset, $weekdayIndex, [
            0 => 5, // Sunday: 5th segment
            1 => 4, // Monday: 4th segment
            2 => 3, // Tuesday: 3rd segment
            3 => 2, // Wednesday: 2nd segment
            4 => 1, // Thursday: 1st segment
            5 => 7, // Friday: 7th segment
            6 => 6, // Saturday: 6th segment
        ], 'Yamaganda');
    }

    /**
     * Calculates Gulika Kaal for the day.
     */
    public function getGulika(DateTimeImmutable $sunrise, DateTimeImmutable $sunset, int $weekdayIndex): TimeWindow
    {
        return $this->getKarmakalaSegment($sunrise, $sunset, $weekdayIndex, [
            0 => 7, // Sunday
            1 => 6, // Monday
            2 => 5, // Tuesday
            3 => 4, // Wednesday
            4 => 3, // Thursday
            5 => 2, // Friday
            6 => 1, // Saturday
        ], 'Gulika Kaal');
    }

    /**
     * @param array<int, int> $map
     */
    private function getKarmakalaSegment(
        DateTimeImmutable $sunrise, 
        DateTimeImmutable $sunset, 
        int $weekdayIndex, 
        array $map,
        string $name
    ): TimeWindow {
        // Divide day into 8 equal segments.
        $totalSeconds = $sunset->getTimestamp() - $sunrise->getTimestamp();
        $segmentLength = $totalSeconds / 8.0;

        $segmentNumber = $map[$weekdayIndex]; // 1-based index
        $index = $segmentNumber - 1;

        $startTs = $sunrise->getTimestamp() + ($index * $segmentLength);
        $endTs = $startTs + $segmentLength;

        return new TimeWindow(
            $name,
            (new DateTimeImmutable())->setTimestamp((int) round($startTs))->setTimezone($sunrise->getTimezone()),
            (new DateTimeImmutable())->setTimestamp((int) round($endTs))->setTimezone($sunrise->getTimezone())
        );
    }
}
