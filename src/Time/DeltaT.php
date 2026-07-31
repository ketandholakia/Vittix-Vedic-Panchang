<?php

declare(strict_types=1);

namespace Vittix\Panchang\Time;

/**
 * Delta T (ΔT) — the difference between Terrestrial Time (TT) and
 * Universal Time (UT1), in seconds.
 *
 *   TT = UT1 + ΔT
 *   JD_TT = JD_UT + ΔT / 86400
 *
 * This implementation uses polynomial approximations from:
 *   Espenak & Meeus, "Five Millennium Canon of Solar Eclipses" (2006)
 *   supplemented by the updated polynomial from Espenak (2014).
 *
 * Supported range: approximately 500 BC – 2200 AD.
 * For years 2005–2050 the uncertainty is a few seconds.
 * For years outside 1900–2100 the uncertainty increases substantially.
 */
final class DeltaT
{
    private function __construct() {}

    /**
     * Estimate ΔT for the given decimal year, returning seconds.
     *
     * @param float $decimalYear  e.g. 2026.5 for mid-2026.
     */
    public static function forYear(float $decimalYear): float
    {
        $y = $decimalYear;

        return match (true) {
            // Before -500
            $y < -500.0 => self::polynomialBefore500($y),

            // -500 to 500
            $y < 500.0 => self::polynomial500To0($y),

            // 500 to 1600
            $y < 1600.0 => self::polynomial500To1600($y),

            // 1600 to 1700
            $y < 1700.0 => self::polynomial1600To1700($y),

            // 1700 to 1800
            $y < 1800.0 => self::polynomial1700To1800($y),

            // 1800 to 1860
            $y < 1860.0 => self::polynomial1800To1860($y),

            // 1860 to 1900
            $y < 1900.0 => self::polynomial1860To1900($y),

            // 1900 to 1920
            $y < 1920.0 => self::polynomial1900To1920($y),

            // 1920 to 1941
            $y < 1941.0 => self::polynomial1920To1941($y),

            // 1941 to 1961
            $y < 1961.0 => self::polynomial1941To1961($y),

            // 1961 to 1986
            $y < 1986.0 => self::polynomial1961To1986($y),

            // 1986 to 2005
            $y < 2005.0 => self::polynomial1986To2005($y),

            // 2005 to recent observed (using interpolation)
            $y < 2026.0 => self::observedRecent($y),

            // Recent to 2050 — Espenak 2014 prediction
            $y < 2050.0 => self::polynomial2005To2050($y),

            // 2050 to 2150
            $y < 2150.0 => self::polynomial2050To2150($y),

            // Beyond 2150
            default     => self::polynomialBeyond2150($y),
        };
    }

    /**
     * Estimate ΔT for a Julian Day in Universal Time.
     */
    public static function forJulianDay(float $julianDayUt): float
    {
        // Approximate decimal year from JD
        $decimalYear = 2000.0 + ($julianDayUt - JulianDay::J2000) / 365.25;

        return self::forYear($decimalYear);
    }

    // -------------------------------------------------------------------------
    // Private polynomial segments (Espenak & Meeus 2006 / Espenak 2014)
    // -------------------------------------------------------------------------

    private static function polynomialBefore500(float $y): float
    {
        $u = ($y - 1820.0) / 100.0;
        return -20.0 + 32.0 * $u ** 2;
    }

    private static function polynomial500To0(float $y): float
    {
        $u = $y / 100.0;
        return 10583.6
            - 1014.41  * $u
            +   33.78311 * $u ** 2
            -    5.952053 * $u ** 3
            -    0.1798452 * $u ** 4
            +    0.022174192 * $u ** 5
            +    0.0090316521 * $u ** 6;
    }

    private static function polynomial500To1600(float $y): float
    {
        $u = ($y - 1000.0) / 100.0;
        return 1574.2
            -  556.01   * $u
            +   71.23472 * $u ** 2
            +    0.319781 * $u ** 3
            -    0.8503463 * $u ** 4
            -    0.005050998 * $u ** 5
            +    0.0083572073 * $u ** 6;
    }

    private static function polynomial1600To1700(float $y): float
    {
        $t = $y - 1600.0;
        return 120.0 - 0.9808 * $t - 0.01532 * $t ** 2 + ($t ** 3) / 7129.0;
    }

    private static function polynomial1700To1800(float $y): float
    {
        $t = $y - 1700.0;
        return 8.83 + 0.1603 * $t - 0.0059285 * $t ** 2 + 0.00013336 * $t ** 3 - ($t ** 4) / 1174000.0;
    }

    private static function polynomial1800To1860(float $y): float
    {
        $t = $y - 1800.0;
        return 13.72 - 0.332447 * $t + 0.0068612 * $t ** 2 + 0.0041116 * $t ** 3
            - 0.00037436 * $t ** 4 + 0.0000121272 * $t ** 5
            - 0.0000001699 * $t ** 6 + 0.000000000875 * $t ** 7;
    }

    private static function polynomial1860To1900(float $y): float
    {
        $t = $y - 1860.0;
        return 7.62 + 0.5737 * $t - 0.251754 * $t ** 2 + 0.01680668 * $t ** 3
            - 0.0004473624 * $t ** 4 + ($t ** 5) / 233174.0;
    }

    private static function polynomial1900To1920(float $y): float
    {
        $t = $y - 1900.0;
        return -2.79 + 1.494119 * $t - 0.0598939 * $t ** 2 + 0.0061966 * $t ** 3 - 0.000197 * $t ** 4;
    }

    private static function polynomial1920To1941(float $y): float
    {
        $t = $y - 1920.0;
        return 21.20 + 0.84493 * $t - 0.076100 * $t ** 2 + 0.0020936 * $t ** 3;
    }

    private static function polynomial1941To1961(float $y): float
    {
        $t = $y - 1950.0;
        return 29.07 + 0.407 * $t - ($t ** 2) / 233.0 + ($t ** 3) / 2547.0;
    }

    private static function polynomial1961To1986(float $y): float
    {
        $t = $y - 1975.0;
        return 45.45 + 1.067 * $t - ($t ** 2) / 260.0 - ($t ** 3) / 718.0;
    }

    private static function polynomial1986To2005(float $y): float
    {
        $t = $y - 2000.0;
        return 63.86 + 0.3345 * $t - 0.060374 * $t ** 2 + 0.0017275 * $t ** 3
            + 0.000651814 * $t ** 4 + 0.00002373599 * $t ** 5;
    }

    private static function polynomial2005To2050(float $y): float
    {
        $t = $y - 2000.0;
        return 62.92 + 0.32217 * $t + 0.005589 * $t ** 2;
    }

    private static function observedRecent(float $y): float
    {
        $observed = [
            2005 => 64.69, 2006 => 64.85, 2007 => 65.15, 2008 => 65.46,
            2009 => 65.78, 2010 => 66.07, 2011 => 66.32, 2012 => 66.60,
            2013 => 66.91, 2014 => 67.28, 2015 => 67.64, 2016 => 68.10,
            2017 => 68.58, 2018 => 68.97, 2019 => 69.22, 2020 => 69.36,
            2021 => 69.26, 2022 => 69.22, 2023 => 69.20, 2024 => 69.11,
            2025 => 69.00, 2026 => 69.00
        ];

        $yFloor = (int) floor($y);
        $yCeil  = $yFloor + 1;

        if (!isset($observed[$yFloor])) {
            return self::polynomial2005To2050($y);
        }

        if (!isset($observed[$yCeil])) {
            return $observed[$yFloor];
        }

        $fraction = $y - $yFloor;
        return $observed[$yFloor] + $fraction * ($observed[$yCeil] - $observed[$yFloor]);
    }

    private static function polynomial2050To2150(float $y): float
    {
        return -20.0 + 32.0 * (($y - 1820.0) / 100.0) ** 2 - 0.5628 * (2150.0 - $y);
    }

    private static function polynomialBeyond2150(float $y): float
    {
        $u = ($y - 1820.0) / 100.0;
        return -20.0 + 32.0 * $u ** 2;
    }
}
