<?php

declare(strict_types=1);

namespace Vittix\Panchang\Calendar;

use DateTimeImmutable;

/**
 * Vikram Samvat and Shaka Samvat year calculators.
 *
 * Both eras are solar-derived offsets from the Gregorian year, adjusted by
 * the Hindu new-year convention (Chaitra Shukla Pratipada).
 *
 * For practical civil use, approximate Gregorian-year offsets are standard.
 * More precise calculation requires knowing whether the Samvat new year
 * (which falls in March–April) has occurred yet in the current Gregorian year.
 *
 * Vikram Samvat:
 *   Starts on Chaitra Shukla Pratipada (usually March/April).
 *   Offset: 57 years ahead of Gregorian (before Chaitra S.P.)
 *            56 years ahead for Jan–Chaitra period.
 *   Example: Gregorian 2026 (after Chaitra) = Vikram Samvat 2083.
 *
 * Shaka Samvat (Indian National Calendar):
 *   Starts on Chaitra 1 = March 22 (or 21 in leap year).
 *   Offset: 78 years behind Gregorian (after March 22).
 *   Example: Gregorian 2026 (after March 22) = Shaka 1948.
 *
 * These are exact for dates after the Shaka new year; before it, subtract 1.
 */
final class SamvatCalculator
{
    /**
     * Vikram Samvat year for a given Gregorian date.
     *
     * Before Chaitra Shukla Pratipada (approximately before April for most years),
     * the Vikram Samvat year is Gregorian + 56.
     * After it, Gregorian + 57.
     *
     * This method uses April 1 as an approximate dividing point.
     * For exact calculation, use the Tithi-aware variant.
     */
    public function vikramSamvat(DateTimeImmutable $date): int
    {
        $year  = (int) $date->format('Y');
        $month = (int) $date->format('n');
        $day   = (int) $date->format('j');

        // Approximate: Chaitra Shukla Pratipada is usually around March 22 – April 14
        // Use April 1 as a safe approximation for year boundary
        $afterNewYear = ($month > 4) || ($month === 4 && $day >= 1);

        return $afterNewYear ? $year + 57 : $year + 56;
    }

    /**
     * Shaka Samvat year for a given Gregorian date.
     *
     * The Shaka year starts on Chaitra 1 = March 22 (leap year: March 21).
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
     * Gujarati Vikram Samvat year.
     *
     * In Gujarat, the new year begins on the day after Diwali (Kartika Shukla
     * Pratipada), which falls in October–November.  This gives a year roughly
     * one year behind the North Indian convention for Oct–Dec dates.
     *
     * Approximate boundary: November 1 as dividing point.
     */
    public function gujaratiSamvat(DateTimeImmutable $date): int
    {
        $year  = (int) $date->format('Y');
        $month = (int) $date->format('n');

        // Before Diwali new year (approx. before November)
        $afterNewYear = $month >= 11;

        return $afterNewYear ? $year + 57 : $year + 56;
    }
}
