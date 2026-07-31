<?php

declare(strict_types=1);

namespace Vittix\Panchang\Muhurta;

use DateTimeImmutable;
use Vittix\Panchang\ValueObject\TimeWindow;

final class MuhurtaCalculator
{
    private const DAY_MUHURTA_NAMES = [
        'Rudra', 'Ahi', 'Mitra', 'Pitru', 'Vasu', 'Vara', 'Vishwadeva',
        'Vidhi', 'Sutamukhi', 'Puruhuta', 'Vahini', 'Naktanchara', 'Varuna', 'Aryama', 'Bhaga'
    ];

    private const NIGHT_MUHURTA_NAMES = [
        'Girish', 'Ajipad', 'Ahirbudhnya', 'Pusha', 'Ashwini', 'Yama', 'Agni',
        'Vidhatru', 'Chanda', 'Aditi', 'Jiva', 'Vishnu', 'Yumigadyuti', 'Thyashur', 'Samudram'
    ];

    /**
     * Get 15 equal parts of the day (Sunrise to Sunset)
     *
     * @return TimeWindow[]
     */
    public function getDayMuhurtas(DateTimeImmutable $sunrise, DateTimeImmutable $sunset): array
    {
        return $this->divideIntoSegments($sunrise, $sunset, 15, self::DAY_MUHURTA_NAMES);
    }

    /**
     * Get 15 equal parts of the night (Sunset to next Sunrise)
     *
     * @return TimeWindow[]
     */
    public function getNightMuhurtas(DateTimeImmutable $sunset, DateTimeImmutable $nextSunrise): array
    {
        return $this->divideIntoSegments($sunset, $nextSunrise, 15, self::NIGHT_MUHURTA_NAMES);
    }

    /**
     * Get 5 equal parts of the day (Sunrise to Sunset) known as Karmakala.
     * These are: Pratahkala, Sangava, Madhyahna, Aparahna, Sayahna.
     *
     * @return TimeWindow[]
     */
    public function getKarmakala(DateTimeImmutable $sunrise, DateTimeImmutable $sunset): array
    {
        $names = ['Pratahkala', 'Sangava', 'Madhyahna', 'Aparahna', 'Sayahna'];
        return $this->divideIntoSegments($sunrise, $sunset, 5, $names);
    }

    /**
     * Calculates Nishita Kaal (the 8th Muhurta of the night, approx midnight).
     * Used for festivals like Maha Shivaratri and Krishna Janmashtami.
     */
    public function getNishita(DateTimeImmutable $sunset, DateTimeImmutable $nextSunrise): TimeWindow
    {
        $muhurtas = $this->getNightMuhurtas($sunset, $nextSunrise);
        // 8th muhurta is index 7
        return $muhurtas[7];
    }

    /**
     * Get 8 Choghadiya segments for the day.
     *
     * @return TimeWindow[]
     */
    public function getDayChoghadiya(DateTimeImmutable $sunrise, DateTimeImmutable $sunset, int $weekdayIndex): array
    {
        $names = $this->getChoghadiyaSequence($weekdayIndex, true);
        return $this->divideIntoSegments($sunrise, $sunset, 8, $names);
    }

    /**
     * Get 8 Choghadiya segments for the night.
     *
     * @return TimeWindow[]
     */
    public function getNightChoghadiya(DateTimeImmutable $sunset, DateTimeImmutable $nextSunrise, int $weekdayIndex): array
    {
        $names = $this->getChoghadiyaSequence($weekdayIndex, false);
        return $this->divideIntoSegments($sunset, $nextSunrise, 8, $names);
    }

    /**
     * Get 24 Hora segments for the full day (Sunrise to next Sunrise).
     *
     * @return TimeWindow[]
     */
    public function getHoras(DateTimeImmutable $sunrise, DateTimeImmutable $nextSunrise, int $weekdayIndex): array
    {
        $totalSeconds = $nextSunrise->getTimestamp() - $sunrise->getTimestamp();
        // Hora divides the day and night into 24 parts (roughly 1 hour each)
        $segmentSeconds = $totalSeconds / 24.0;

        $windows = [];
        $currentTs = (float) $sunrise->getTimestamp();
        
        $horaSequence = $this->getHoraSequence($weekdayIndex);

        for ($i = 0; $i < 24; $i++) {
            $windowStart = (new \DateTimeImmutable())->setTimestamp((int) round($currentTs))->setTimezone($sunrise->getTimezone());
            $currentTs += $segmentSeconds;
            $windowEnd = (new \DateTimeImmutable())->setTimestamp((int) round($currentTs))->setTimezone($sunrise->getTimezone());
            
            $nameIndex = $i % 7;
            
            $windows[] = new TimeWindow($horaSequence[$nameIndex], $windowStart, $windowEnd);
        }

        return $windows;
    }

    /**
     * @param array<int, string> $names
     * @return array<int, TimeWindow>
     */
    private function divideIntoSegments(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        int $parts,
        array $names
    ): array {
        $totalSeconds = $end->getTimestamp() - $start->getTimestamp();
        $segmentSeconds = $totalSeconds / $parts;

        $windows = [];
        $currentTs = (float) $start->getTimestamp();

        for ($i = 0; $i < $parts; $i++) {
            $windowStart = (new \DateTimeImmutable())->setTimestamp((int) round($currentTs))->setTimezone($start->getTimezone());
            $currentTs += $segmentSeconds;
            $windowEnd = (new \DateTimeImmutable())->setTimestamp((int) round($currentTs))->setTimezone($start->getTimezone());
            
            // Name might repeat if sequence is shorter than parts (like Choghadiya which only has 7 unique names but 8 parts)
            $nameIndex = $i % count($names);
            
            $windows[] = new TimeWindow($names[$nameIndex], $windowStart, $windowEnd);
        }

        return $windows;
    }

    /**
     * @return array<int, string>
     */
    private function getChoghadiyaSequence(int $weekdayIndex, bool $isDay): array
    {
        // 0 = Sunday, 1 = Monday, etc.
        // Choghadiya patterns are fixed based on weekday.
        $baseDaySequence = [
            0 => ['Udveg', 'Chal', 'Labh', 'Amrit', 'Kaal', 'Shubh', 'Rog', 'Udveg'], // Sun
            1 => ['Amrit', 'Kaal', 'Shubh', 'Rog', 'Udveg', 'Chal', 'Labh', 'Amrit'], // Mon
            2 => ['Rog', 'Udveg', 'Chal', 'Labh', 'Amrit', 'Kaal', 'Shubh', 'Rog'],   // Tue
            3 => ['Labh', 'Amrit', 'Kaal', 'Shubh', 'Rog', 'Udveg', 'Chal', 'Labh'],  // Wed
            4 => ['Shubh', 'Rog', 'Udveg', 'Chal', 'Labh', 'Amrit', 'Kaal', 'Shubh'], // Thu
            5 => ['Chal', 'Labh', 'Amrit', 'Kaal', 'Shubh', 'Rog', 'Udveg', 'Chal'],  // Fri
            6 => ['Kaal', 'Shubh', 'Rog', 'Udveg', 'Chal', 'Labh', 'Amrit', 'Kaal'],  // Sat
        ];

        $baseNightSequence = [
            0 => ['Shubh', 'Amrit', 'Chal', 'Rog', 'Kaal', 'Labh', 'Udveg', 'Shubh'], // Sun
            1 => ['Chal', 'Rog', 'Kaal', 'Labh', 'Udveg', 'Shubh', 'Amrit', 'Chal'],  // Mon
            2 => ['Rog', 'Kaal', 'Labh', 'Udveg', 'Shubh', 'Amrit', 'Chal', 'Rog'],   // Tue
            3 => ['Kaal', 'Labh', 'Udveg', 'Shubh', 'Amrit', 'Chal', 'Rog', 'Kaal'],  // Wed
            4 => ['Amrit', 'Chal', 'Rog', 'Kaal', 'Labh', 'Udveg', 'Shubh', 'Amrit'], // Thu
            5 => ['Rog', 'Kaal', 'Labh', 'Udveg', 'Shubh', 'Amrit', 'Chal', 'Rog'],   // Fri
            6 => ['Labh', 'Udveg', 'Shubh', 'Amrit', 'Chal', 'Rog', 'Kaal', 'Labh'],  // Sat
        ];

        return $isDay ? $baseDaySequence[$weekdayIndex] : $baseNightSequence[$weekdayIndex];
    }

    /**
     * @return array<int, string>
     */
    private function getHoraSequence(int $weekdayIndex): array
    {
        // 0 = Sunday, 1 = Monday, 2 = Tuesday, 3 = Wednesday, 4 = Thursday, 5 = Friday, 6 = Saturday
        // The ruler of the first Hora of the day is the Lord of the day.
        // Subsequent Horas follow the decreasing order of planetary speeds:
        // Saturn, Jupiter, Mars, Sun, Venus, Mercury, Moon.
        $lords = ['Sun', 'Venus', 'Mercury', 'Moon', 'Saturn', 'Jupiter', 'Mars'];
        
        // Start index for each weekday
        $starts = [
            0 => 0, // Sunday -> Sun
            1 => 3, // Monday -> Moon
            2 => 6, // Tuesday -> Mars
            3 => 2, // Wednesday -> Mercury
            4 => 5, // Thursday -> Jupiter
            5 => 1, // Friday -> Venus
            6 => 4, // Saturday -> Saturn
        ];
        
        $start = $starts[$weekdayIndex];
        $sequence = [];
        for ($i = 0; $i < 7; $i++) {
            $sequence[] = $lords[($start + $i) % 7];
        }
        
        return $sequence;
    }
}
