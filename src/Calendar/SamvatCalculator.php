<?php

declare(strict_types=1);

namespace Vittix\Panchang\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Festivals\NewYearResolver;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Vikram Samvat and Shaka Samvat year calculators.
 *
 * Exact rules (lunar-anchored):
 *
 *   • Vikram Samvat changes at Chaitra Shukla Pratipada (Yugadi / Gudi
 *     Padwa) — the civil day resolved by the SAME rule the festival engine
 *     uses for Shukla Pratipada (see Festivals\NewYearResolver), including
 *     the greater-overlap fallback when the tithi never prevails at a
 *     sunrise. The year changes at 00:00 local on that day:
 *     VS = Gregorian year of the new-year day + 57.
 *   • Shaka Samvat changes on the same Chaitra Shukla Pratipada.
 *     After it, Shaka = Gregorian year of the new-year day − 78.
 *   • Gujarati Vikram Samvat changes at Kartika Shukla Pratipada (the day
 *     after Diwali), resolved by the same shared rule. After it, the year
 *     number equals the Vikram year number (the year that began at the
 *     previous Chaitra).
 *
 * The exact variants ({@see vikramSamvatExact()} etc.) resolve the lunar
 * new year via the shared {@see NewYearResolver}. The plain date-only methods
 * are documented approximations kept for convenience and backward
 * compatibility: they use fixed Gregorian dividing points (April 1,
 * March 22, November 1) and can be off by days to weeks around the real
 * boundaries.
 */
final class SamvatCalculator
{
    private ?NewYearResolver $newYearResolver = null;

    public function __construct(
        private readonly ?LunarMonthCalculator $lunarMonth = null,
        private readonly ?AstronomyEngineInterface $astronomy = null,
    ) {
    }

    /**
     * Whether the exact (lunar-anchored) methods are usable.
     */
    public function hasLunarSupport(): bool
    {
        return $this->lunarMonth !== null && $this->astronomy !== null;
    }

    /**
     * Exact Vikram Samvat year: changes at Chaitra Shukla Pratipada.
     */
    public function vikramSamvatExact(AstroInstant $instant, GeoLocation $location, ?DateTimeZone $timezone = null): int
    {
        return $this->lunarNewYearGregorianYear($instant, $location, $timezone, LunarMonth::Chaitra) + 57;
    }

    /**
     * Exact Shaka Samvat year: changes at Chaitra Shukla Pratipada.
     */
    public function shakaSamvatExact(AstroInstant $instant, GeoLocation $location, ?DateTimeZone $timezone = null): int
    {
        return $this->lunarNewYearGregorianYear($instant, $location, $timezone, LunarMonth::Chaitra) - 78;
    }

    /**
     * Exact Gujarati Vikram Samvat year: changes at Kartika Shukla
     * Pratipada (the day after Diwali).
     */
    public function gujaratiSamvatExact(AstroInstant $instant, GeoLocation $location, ?DateTimeZone $timezone = null): int
    {
        return $this->lunarNewYearGregorianYear($instant, $location, $timezone, LunarMonth::Kartika) + 57;
    }

    // -------------------------------------------------------------------------
    // Approximate, date-only variants (kept for convenience / compatibility)
    // -------------------------------------------------------------------------

    /**
     * Approximate Vikram Samvat year for a given Gregorian date.
     *
     * Uses April 1 as an approximate dividing point for Chaitra Shukla
     * Pratipada, which actually falls between mid-March and mid-April.
     *
     * @deprecated Use {@see vikramSamvatExact()} for the lunar-anchored year.
     */
    public function vikramSamvat(DateTimeImmutable $date): int
    {
        $month = (int) $date->format('n');

        // Approximate: Chaitra Shukla Pratipada is usually around March 22 – April 14
        // Use April 1 as a safe approximation for year boundary
        $afterNewYear = $month >= 4;

        return $afterNewYear ? (int) $date->format('Y') + 57 : (int) $date->format('Y') + 56;
    }

    /**
     * Approximate Shaka Samvat year for a given Gregorian date.
     *
     * The Shaka year starts on Chaitra Shukla Pratipada (a lunar date,
     * mid-March to mid-April); this approximation uses the Indian National
     * Calendar's fixed Chaitra 1 = March 22 (March 21 in leap years).
     *
     * @deprecated Use {@see shakaSamvatExact()} for the lunar-anchored year.
     */
    public function shakaSamvat(DateTimeImmutable $date): int
    {
        $year  = (int) $date->format('Y');
        $month = (int) $date->format('n');
        $day   = (int) $date->format('j');

        $isLeap      = (int) $date->format('L') === 1;
        $newYearDay  = $isLeap ? 21 : 22; // March 21 (leap) or March 22

        $afterNewYear = ($month > 3) || ($month === 3 && $day >= $newYearDay);

        return $afterNewYear ? $year - 78 : $year - 79;
    }

    /**
     * Approximate Gujarati Vikram Samvat year.
     *
     * Uses November 1 as an approximate dividing point for Kartika Shukla
     * Pratipada (the day after Diwali), which actually falls between
     * mid-October and mid-November.
     *
     * @deprecated Use {@see gujaratiSamvatExact()} for the lunar-anchored year.
     */
    public function gujaratiSamvat(DateTimeImmutable $date): int
    {
        $year  = (int) $date->format('Y');
        $month = (int) $date->format('n');

        // Before Diwali new year (approx. before November)
        $afterNewYear = $month >= 11;

        return $afterNewYear ? $year + 57 : $year + 56;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Gregorian year (in $timezone) of the most recent new-year day at or
     * before $instant, where the new year is the Shukla Pratipada day of a
     * (non-Adhika) lunation named $month — resolved with the same rule the
     * festival engine uses ({@see NewYearResolver}), including the
     * greater-overlap fallback. The year changes at 00:00 local on that
     * civil day.
     */
    private function lunarNewYearGregorianYear(
        AstroInstant $instant,
        GeoLocation $location,
        ?DateTimeZone $timezone,
        LunarMonth $month,
    ): int {
        if ($this->lunarMonth === null || $this->astronomy === null) {
            throw new RuntimeException(
                'Exact Samvat calculation requires a LunarMonthCalculator and an astronomy engine. '
                . 'Construct SamvatCalculator with both, or use the approximate date-only methods.'
            );
        }

        $tz = $timezone ?? $instant->utc->getTimezone();

        return $this->newYearResolver()->newYearGregorianYear($month, $instant, $location, $tz);
    }

    private function newYearResolver(): NewYearResolver
    {
        assert($this->lunarMonth !== null && $this->astronomy !== null);

        return $this->newYearResolver ??= new NewYearResolver(
            $this->astronomy,
            new TithiCalculator($this->astronomy),
            $this->lunarMonth
        );
    }
}
