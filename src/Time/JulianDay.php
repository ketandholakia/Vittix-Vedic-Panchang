<?php

declare(strict_types=1);

namespace Vittix\Panchang\Time;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Converts between Gregorian calendar dates and Julian Day Numbers.
 *
 * Julian Day Number (JDN) — integer part only, starts at noon UTC.
 * Julian Day (JD)         — continuous decimal, noon UTC = integer JDN.
 *
 * All methods work in Universal Time (UT / UTC).
 * The conversion algorithm follows Jean Meeus, "Astronomical Algorithms",
 * 2nd edition, Chapter 7.
 *
 * Reference epoch:
 *   J2000.0 = JD 2451545.0 = 2000 January 1.5 TT = 2000-01-01 12:00 TT
 */
final class JulianDay
{
    /** Julian Day of J2000.0 epoch */
    public const J2000 = 2451545.0;

    /** Julian Days in one Julian century */
    public const DAYS_PER_CENTURY = 36525.0;

    /** Julian Days in one Julian millennium */
    public const DAYS_PER_MILLENNIUM = 365250.0;

    private function __construct() {}

    // -------------------------------------------------------------------------
    // Gregorian → JD
    // -------------------------------------------------------------------------

    /**
     * Convert a UTC DateTimeImmutable to its Julian Day (decimal).
     *
     * The input is coerced to UTC before conversion, so any timezone offset
     * is accounted for automatically.
     */
    public static function fromUtc(DateTimeImmutable $utc): float
    {
        // Normalise to UTC so any offset is stripped.
        $utc = $utc->setTimezone(new DateTimeZone('UTC'));

        $year  = (int) $utc->format('Y');
        $month = (int) $utc->format('n');
        $day   = (int) $utc->format('j');

        // Hours, minutes, seconds as a decimal fraction of a day.
        $hour   = (int) $utc->format('H');
        $minute = (int) $utc->format('i');
        $second = (int) $utc->format('s');
        $dayFraction = ($hour + $minute / 60.0 + $second / 3600.0) / 24.0;

        return self::fromGregorian($year, $month, (float) $day + $dayFraction);
    }

    /**
     * Low-level Gregorian (year, month, decimal-day) → JD.
     *
     * Handles the Julian/Gregorian calendar reform at 1582-10-15 correctly.
     * For astronomical purposes dates before 1582 use the Julian calendar.
     *
     * @param int   $year       Full Gregorian year (negative = BC, 0 = 1 BC).
     * @param int   $month      Month number 1–12.
     * @param float $day        Day of month with decimal fraction.
     */
    public static function fromGregorian(int $year, int $month, float $day): float
    {
        if ($month <= 2) {
            $year  -= 1;
            $month += 12;
        }

        // Gregorian calendar correction
        $a = (int) floor($year / 100.0);
        $b = 2 - $a + (int) floor($a / 4.0);

        // Julian calendar (before 1582-10-15): B = 0
        if ($year < 1582 || ($year === 1582 && ($month < 10 || ($month === 10 && $day < 15.0)))) {
            $b = 0;
        }

        return floor(365.25 * ($year + 4716))
             + floor(30.6001 * ($month + 1))
             + $day
             + $b
             - 1524.5;
    }

    // -------------------------------------------------------------------------
    // JD → Gregorian
    // -------------------------------------------------------------------------

    /**
     * Convert a Julian Day (UT) to a UTC DateTimeImmutable.
     */
    public static function toUtc(float $julianDay): DateTimeImmutable
    {
        [$year, $month, $day] = self::toGregorian($julianDay);

        $wholeDays    = (int) floor($day);
        $dayFraction  = $day - $wholeDays;

        $totalSeconds = (int) round($dayFraction * 86400.0);
        $hour   = intdiv($totalSeconds, 3600);
        $minute = intdiv($totalSeconds % 3600, 60);
        $second = $totalSeconds % 60;

        $str = sprintf(
            '%04d-%02d-%02d %02d:%02d:%02d',
            $year, $month, $wholeDays,
            $hour, $minute, $second
        );

        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $str, new DateTimeZone('UTC'));

        if ($dt === false) {
            throw new InvalidArgumentException("Could not convert JD {$julianDay} to DateTimeImmutable.");
        }

        return $dt;
    }

    /**
     * Low-level JD → [year, month, decimal-day] in the Gregorian calendar.
     *
     * @return array{0: int, 1: int, 2: float}  [year, month, decimal-day]
     */
    public static function toGregorian(float $julianDay): array
    {
        $jd = $julianDay + 0.5;
        $z  = (int) floor($jd);
        $f  = $jd - $z;

        if ($z < 2299161) {
            $a = $z;
        } else {
            $alpha = (int) floor(($z - 1867216.25) / 36524.25);
            $a     = $z + 1 + $alpha - (int) floor($alpha / 4.0);
        }

        $b = $a + 1524;
        $c = (int) floor(($b - 122.1) / 365.25);
        $d = (int) floor(365.25 * $c);
        $e = (int) floor(($b - $d) / 30.6001);

        $day   = (float) ($b - $d - (int) floor(30.6001 * $e)) + $f;
        $month = ($e < 14) ? $e - 1 : $e - 13;
        $year  = ($month > 2) ? $c - 4716 : $c - 4715;

        return [$year, $month, $day];
    }

    // -------------------------------------------------------------------------
    // Time-scale helpers
    // -------------------------------------------------------------------------

    /**
     * Julian centuries from J2000.0 for a given JD (Terrestrial Time).
     *
     * This is the "T" used in almost every astronomical formula.
     */
    public static function julianCenturiesFromJ2000(float $julianDayTt): float
    {
        return ($julianDayTt - self::J2000) / self::DAYS_PER_CENTURY;
    }

    /**
     * Julian millennia from J2000.0 (used by VSOP87).
     */
    public static function julianMillenniaFromJ2000(float $julianDayTt): float
    {
        return ($julianDayTt - self::J2000) / self::DAYS_PER_MILLENNIUM;
    }
}
