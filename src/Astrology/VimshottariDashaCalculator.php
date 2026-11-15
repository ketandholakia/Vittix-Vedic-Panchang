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

        // The first Mahadasha began before birth: its true start is the birth
        // instant minus the elapsed fraction of its full length. The
        // Antardasha timeline must be built from that true start (the
        // Antardasha running at birth is then listed first, truncated) —
        // never from birth with the sub-periods scaled to the remainder.
        $firstBody = self::SEQUENCE[$startIndex];
        $firstYears = self::YEARS[$firstBody->value];
        $elapsedSeconds = (int) round($firstYears * $fractionTraversed * self::YEAR_IN_SECONDS);
        $trueStart = $birthTime->sub(new DateInterval('PT' . $elapsedSeconds . 'S'));

        $currentStart = $birthTime;

        for ($i = 0; $i < 9; $i++) {
            $currentIndex = ($startIndex + $i) % 9;
            $body = self::SEQUENCE[$currentIndex];
            $years = self::YEARS[$body->value];

            // For the very first Mahadasha, we only live through the remaining fraction
            $actualYears = ($i === 0) ? ($years * $fractionRemaining) : $years;

            $seconds = (int) round($actualYears * self::YEAR_IN_SECONDS);
            $currentEnd = $currentStart->add(new DateInterval('PT' . $seconds . 'S'));

            // Antardashas span the FULL Mahadasha (from its true start);
            // parts before the visible window are dropped/truncated.
            $antardashaTrueStart = ($i === 0) ? $trueStart : $currentStart;
            $subPeriods = $this->calculateAntardashas(
                $body,
                $years,
                $antardashaTrueStart,
                $currentStart,
                $currentEnd
            );

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
     * Antardashas of one Mahadasha.
     *
     * The nine sub-periods are laid out at their FULL proportional lengths
     * (years × mahadasha years / 120) from the Mahadasha's true start; the
     * ones entirely before $visibleStart are dropped and the first running
     * one is truncated at it. Every listed period is clipped to
     * $visibleEnd, so the listed lengths always sum exactly to the visible
     * span (for every Mahadasha except the first, that is the full length).
     *
     * @return array<DashaPeriod>
     */
    private function calculateAntardashas(
        Body $mahadashaLord,
        float $mahadashaYears,
        DateTimeImmutable $trueStart,
        DateTimeImmutable $visibleStart,
        DateTimeImmutable $visibleEnd
    ): array {
        // Find the index of the Mahadasha lord in the sequence to start the sub-periods
        $startIndex = 0;
        foreach (self::SEQUENCE as $index => $body) {
            if ($body === $mahadashaLord) {
                $startIndex = $index;
                break;
            }
        }

        // Full length of this Mahadasha in seconds (the first one's visible
        // span is only the remaining fraction).
        $fullSeconds = (int) round($mahadashaYears * self::YEAR_IN_SECONDS);
        $fullEnd = $trueStart->add(new DateInterval('PT' . $fullSeconds . 'S'));

        $subPeriods = [];
        $currentStart = $trueStart;

        for ($i = 0; $i < 9; $i++) {
            $currentIndex = ($startIndex + $i) % 9;
            $body = self::SEQUENCE[$currentIndex];

            // Full proportion: (Antardasha Years / 120) × Mahadasha years.
            $proportion = self::YEARS[$body->value] * $mahadashaYears / self::TOTAL_YEARS;
            $seconds = (int) round($proportion * self::YEAR_IN_SECONDS);

            $currentEnd = $currentStart->add(new DateInterval('PT' . $seconds . 'S'));

            // The last period should exactly match the full end to avoid rounding gaps
            if ($i === 8) {
                $currentEnd = $fullEnd;
            }

            // Drop Antardashas that ended before the visible window;
            // truncate the one running at $visibleStart.
            if ($currentEnd->getTimestamp() > $visibleStart->getTimestamp()) {
                $listedStart = $currentStart->getTimestamp() < $visibleStart->getTimestamp()
                    ? $visibleStart
                    : $currentStart;
                $listedEnd = $currentEnd->getTimestamp() > $visibleEnd->getTimestamp()
                    ? $visibleEnd
                    : $currentEnd;

                $subPeriods[] = new DashaPeriod(
                    body: $body,
                    start: $listedStart,
                    end: $listedEnd
                );
            }

            $currentStart = $currentEnd;
        }

        return $subPeriods;
    }
}
