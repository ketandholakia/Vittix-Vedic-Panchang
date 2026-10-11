<?php

declare(strict_types=1);

namespace Vittix\Panchang\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Enum\CalendarSystem;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Paksha;
use Vittix\Panchang\Enum\SolarMonth;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Result of a Hindu calendar calculation for a given moment.
 */
final readonly class HinduCalendarResult
{
    public function __construct(
        public readonly ?LunarMonth         $amantaMonth,
        public readonly ?LunarMonth         $purnimantaMonth,
        public readonly bool                $isAdhikaMasa,
        public readonly ?DateTimeImmutable  $previousNewMoon,
        public readonly ?DateTimeImmutable  $nextNewMoon,
        public readonly ?DateTimeImmutable  $nextFullMoon,
        public readonly int                 $vikramSamvat,
        public readonly int                 $shakaSamvat,
        public readonly int                 $gujaratiSamvat,
        public readonly ?SolarMonth         $solarMonth,
    ) {
    }
}

/**
 * Orchestrates all Hindu calendar components into a single result.
 *
 * Provides:
 *   • Amanta lunar month (ends at new moon)
 *   • Purnimanta lunar month (ends at full moon — month name shifted in Krishna Paksha)
 *   • Adhika Masa detection
 *   • Previous/next new moon times
 *   • Next full moon time
 *   • Vikram Samvat year
 *   • Shaka Samvat year
 */
final class HinduCalendarCalculator
{
    public function __construct(
        private readonly LunarMonthCalculator  $lunarMonth,
        private readonly AdhikaMasaCalculator  $adhikaMasa,
        private readonly SamvatCalculator      $samvat,
        private readonly PurnimantaMonthCalculator $purnimantaMonthCalc = new PurnimantaMonthCalculator(),
    ) {
    }

    /**
     * Calculate the complete Hindu calendar context for a given moment.
     *
     * @param AstroInstant     $instant  The query moment.
     * @param DateTimeZone     $timezone Local timezone for output times.
     */
    public function calculate(
        AstroInstant $instant,
        DateTimeZone $timezone
    ): HinduCalendarResult {
        $prevNm    = $this->lunarMonth->previousNewMoon($instant);
        $nextNm    = $this->lunarMonth->nextNewMoon($instant);
        $nextFm    = $this->lunarMonth->nextFullMoon($instant);

        $amanta    = $this->lunarMonth->amantaMonth($instant);
        $adhika    = $this->adhikaMasa->isAdhikaMasa($instant);

        // Purnimanta: same as Amanta in Shukla Paksha;
        // one month ahead in Krishna Paksha (after full moon).
        // For a simple implementation: check if we're past the full moon.
        $purnimanta = $this->derivePurnimantaMonth($instant, $amanta);

        $gregorianDate = $instant->utc->setTimezone($timezone);

        return new HinduCalendarResult(
            amantaMonth: $amanta,
            purnimantaMonth: $purnimanta,
            isAdhikaMasa: $adhika,
            previousNewMoon: $prevNm->utc->setTimezone($timezone),
            nextNewMoon: $nextNm->utc->setTimezone($timezone),
            nextFullMoon: $nextFm->utc->setTimezone($timezone),
            vikramSamvat: $this->samvat->vikramSamvat($gregorianDate),
            shakaSamvat: $this->samvat->shakaSamvat($gregorianDate),
            gujaratiSamvat: $this->samvat->gujaratiSamvat($gregorianDate),
            solarMonth: null, // Solar month requires the astronomy engine; set by PanchangService
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Derive the Purnimanta month from the Amanta month.
     *
     * In Purnimanta reckoning:
     *  • Shukla Paksha: same month name as Amanta.
     *  • Krishna Paksha: the month name is ONE AHEAD of Amanta.
     *    (The Purnimanta Krishna Paksha "belongs" to the next month.)
     *
     * The month-shift rule itself is single-sourced in
     * {@see PurnimantaMonthCalculator}; this method only detects the Paksha
     * (by phase-event order: past the full moon = Krishna) and delegates.
     */
    private function derivePurnimantaMonth(
        AstroInstant $instant,
        ?LunarMonth $amantaMonth
    ): ?LunarMonth {
        if ($amantaMonth === null) {
            return null;
        }

        // Check whether we are in Krishna Paksha (past full moon)
        $prevFm = $this->lunarMonth->previousFullMoon($instant);
        $prevNm = $this->lunarMonth->previousNewMoon($instant);

        $inKrishnaPaksha = $prevFm->julianDayUt > $prevNm->julianDayUt;

        return $this->purnimantaMonthCalc->purnimantaMonth(
            $amantaMonth,
            $inKrishnaPaksha ? Paksha::Krishna : Paksha::Shukla,
        );
    }
}
