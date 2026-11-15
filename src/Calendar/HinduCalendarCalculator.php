<?php

declare(strict_types=1);

namespace Vittix\Panchang\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Enum\CalendarSystem;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\SolarMonth;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Result of a Hindu calendar calculation for a given moment.
 */
final readonly class HinduCalendarResult
{
    public function __construct(
        public readonly ?LunarMonth         $amantaMonth,
        public readonly ?LunarMonth         $purnimantaMonth,
        public readonly bool                $isAdhikaMasa,
        public readonly bool                $purnimantaIsAdhikaMasa,
        public readonly ?DateTimeImmutable  $previousNewMoon,
        public readonly ?DateTimeImmutable  $nextNewMoon,
        public readonly ?DateTimeImmutable  $nextFullMoon,
        public readonly int                 $vikramSamvat,
        public readonly int                 $shakaSamvat,
        public readonly int                 $gujaratiSamvat,
        public readonly ?SolarMonth         $solarMonth,
        /**
         * True when the Samvat years above come from the documented
         * calendar-month approximations rather than the exact lunar
         * new-year resolution (no location was supplied, or the
         * SamvatCalculator has no lunar support).
         */
        public readonly bool                $samvatIsApproximate = false,
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
    private readonly PurnimantaMonthCalculator $purnimantaMonthCalc;

    public function __construct(
        private readonly LunarMonthCalculator  $lunarMonth,
        private readonly AdhikaMasaCalculator  $adhikaMasa,
        private readonly SamvatCalculator      $samvat,
        ?PurnimantaMonthCalculator $purnimantaMonthCalc = null,
    ) {
        $this->purnimantaMonthCalc = $purnimantaMonthCalc ?? new PurnimantaMonthCalculator($lunarMonth);
    }

    /**
     * Calculate the complete Hindu calendar context for a given moment.
     *
     * @param AstroInstant     $instant  The query moment.
     * @param DateTimeZone     $timezone Local timezone for output times.
     * @param GeoLocation|null $location Observer location. When given (and
     *                                 the SamvatCalculator was built with
     *                                 the lunar machinery) the Samvat years
     *                                 are computed exactly from the lunar
     *                                 new years; otherwise the deprecated
     *                                 date-only approximations are used.
     */
    public function calculate(
        AstroInstant $instant,
        DateTimeZone $timezone,
        ?GeoLocation $location = null
    ): HinduCalendarResult {
        $prevNm    = $this->lunarMonth->previousNewMoon($instant);
        $nextNm    = $this->lunarMonth->nextNewMoon($instant);
        $nextFm    = $this->lunarMonth->nextFullMoon($instant);

        $amanta    = $this->lunarMonth->amantaMonth($instant);
        $adhika    = $this->adhikaMasa->isAdhikaMasa($instant);

        // Purnimanta: full-moon-to-full-moon month named after the Amanta
        // lunation that follows its starting full moon (correct around
        // Adhika/Kshaya months, unlike a blind paksha-based shift).
        $purnimanta = $this->purnimantaMonthCalc->monthAt($instant);

        $gregorianDate = $instant->utc->setTimezone($timezone);

        $exact = $location !== null && $this->samvat->hasLunarSupport();
        if ($exact) {
            $vikram    = $this->samvat->vikramSamvatExact($instant, $location, $timezone);
            $shaka     = $this->samvat->shakaSamvatExact($instant, $location, $timezone);
            $gujarati  = $this->samvat->gujaratiSamvatExact($instant, $location, $timezone);
        } else {
            $vikram    = $this->samvat->vikramSamvat($gregorianDate);
            $shaka     = $this->samvat->shakaSamvat($gregorianDate);
            $gujarati  = $this->samvat->gujaratiSamvat($gregorianDate);
        }

        return new HinduCalendarResult(
            amantaMonth: $amanta,
            purnimantaMonth: $purnimanta->month,
            isAdhikaMasa: $adhika,
            purnimantaIsAdhikaMasa: $purnimanta->isAdhika,
            previousNewMoon: $prevNm->utc->setTimezone($timezone),
            nextNewMoon: $nextNm->utc->setTimezone($timezone),
            nextFullMoon: $nextFm->utc->setTimezone($timezone),
            vikramSamvat: $vikram,
            shakaSamvat: $shaka,
            gujaratiSamvat: $gujarati,
            solarMonth: null, // Solar month requires the astronomy engine; set by PanchangService
            samvatIsApproximate: !$exact,
        );
    }
}
