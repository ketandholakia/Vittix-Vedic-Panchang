<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology;

use DateInterval;
use DateTimeImmutable;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\ValueObject\DashaPeriod;

/**
 * Calculates the Vimshottari Dasha (120-year cycle of planetary periods).
 */
final class VimshottariDashaCalculator
{
    private const TOTAL_YEARS = 120.0;
    
    // Average length of a Gregorian year in seconds
    private const YEAR_IN_SECONDS = 31556952.0; // 365.2425 days

    // The ruling lords of the 27 Nakshatras starting from Ashwini.
    // The sequence repeats every 9 Nakshatras.
    private const SEQUENCE = [
        Body::KetuMean,
        Body::Venus,
        Body::Sun,
        Body::Moon,
        Body::Mars,
        Body::RahuMean,
        Body::Jupiter,
        Body::Saturn,
        Body::Mercury,
    ];

    private const YEARS = [
        Body::KetuMean->value => 7.0,
        Body::Venus->value => 20.0,
        Body::Sun->value => 6.0,
        Body::Moon->value => 10.0,
        Body::Mars->value => 7.0,
        Body::RahuMean->value => 18.0,
        Body::Jupiter->value => 16.0,
        Body::Saturn->value => 19.0,
        Body::Mercury->value => 17.0,
    ];

    /**
     * Calculates the Vimshottari Mahadashas (and their Antardashas) spanning 120 years.
     *
     * @param DateTimeImmutable $birthTime The exact time of birth.
     * @param float $moonLongitude The exact sidereal longitude of the Moon at birth.
     * @return array<DashaPeriod>
     */
    public function calculate(DateTimeImmutable $birthTime, float $moonLongitude): array
    {
        $moonLongitude = Angle::normalize($moonLongitude);
        
        // A Nakshatra is exactly 360 / 27 = 13.3333333 degrees
        $nakshatraLength = 360.0 / 27.0;
        $nakshatraExactPosition = $moonLongitude / $nakshatraLength;
        
        $nakshatraIndex = (int) floor($nakshatraExactPosition);
        $fractionTraversed = $nakshatraExactPosition - $nakshatraIndex;
        $fractionRemaining = 1.0 - $fractionTraversed;

        $startIndex = $nakshatraIndex % 9;
        
        $dashas = [];
        $currentStart = $birthTime;

        for ($i = 0; $i < 9; $i++) {
            $currentIndex = ($startIndex + $i) % 9;
            $body = self::SEQUENCE[$currentIndex];
            $years = self::YEARS[$body->value];

            // For the very first Mahadasha, we only live through the remaining fraction
            $actualYears = ($i === 0) ? ($years * $fractionRemaining) : $years;
            
            $seconds = (int) round($actualYears * self::YEAR_IN_SECONDS);
            $currentEnd = $currentStart->add(new DateInterval('PT' . $seconds . 'S'));

            // Calculate Antardashas
            $subPeriods = $this->calculateAntardashas($body, $years, $currentStart, $currentEnd);

            $dashas[] = new DashaPeriod(
                body: $body,
                start: $currentStart,
                end: $currentEnd,
                subPeriods: $subPeriods
            );

            $currentStart = $currentEnd;
        }

        return $dashas;
    }

    /**
     * @return array<DashaPeriod>
     */
    private function calculateAntardashas(
        Body $mahadashaLord, 
        float $mahadashaYears, 
        DateTimeImmutable $mahadashaStart, 
        DateTimeImmutable $mahadashaEnd
    ): array {
        // Find the index of the Mahadasha lord in the sequence to start the sub-periods
        $startIndex = 0;
        foreach (self::SEQUENCE as $index => $body) {
            if ($body === $mahadashaLord) {
                $startIndex = $index;
                break;
            }
        }

        $subPeriods = [];
        $currentStart = $mahadashaStart;
        
        // Total actual duration of this segment in seconds (might be fractional if first period)
        $totalSeconds = $mahadashaEnd->getTimestamp() - $mahadashaStart->getTimestamp();

        for ($i = 0; $i < 9; $i++) {
            $currentIndex = ($startIndex + $i) % 9;
            $body = self::SEQUENCE[$currentIndex];
            $antardashaYears = self::YEARS[$body->value];
            
            // The proportion of the Antardasha is (Antardasha Years / 120) * Total Segment Duration
            $proportion = $antardashaYears / self::TOTAL_YEARS;
            $seconds = (int) round($proportion * $totalSeconds);

            $currentEnd = $currentStart->add(new DateInterval('PT' . $seconds . 'S'));

            // The last period should exactly match the end to avoid rounding gaps
            if ($i === 8) {
                $currentEnd = $mahadashaEnd;
            }

            $subPeriods[] = new DashaPeriod(
                body: $body,
                start: $currentStart,
                end: $currentEnd
            );

            $currentStart = $currentEnd;
        }

        return $subPeriods;
    }
}
