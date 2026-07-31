<?php

declare(strict_types=1);

namespace Vittix\Panchang\Math;

/**
 * Angle arithmetic utilities.
 *
 * All methods are stateless and accept/return degrees unless the name says
 * "radians".  No state is held — these are pure mathematical helpers.
 */
final class Angle
{
    private function __construct() {}

    /**
     * Normalise degrees to the half-open interval [0, 360).
     *
     * Works correctly for negative values, values greater than 360,
     * and large magnitudes.
     */
    public static function normalize(float $degrees): float
    {
        $value = fmod($degrees, 360.0);

        return $value < 0.0 ? $value + 360.0 : $value;
    }

    /**
     * Normalise degrees to the closed interval (-180, 180].
     *
     * Useful for computing signed angular differences where crossing 0°/360°
     * would otherwise produce a discontinuity.
     */
    public static function normalizeSigned(float $degrees): float
    {
        $value = self::normalize($degrees);

        return $value > 180.0 ? $value - 360.0 : $value;
    }

    /**
     * Convert degrees to radians.
     */
    public static function toRadians(float $degrees): float
    {
        return deg2rad($degrees);
    }

    /**
     * Convert radians to degrees.
     */
    public static function toDegrees(float $radians): float
    {
        return rad2deg($radians);
    }

    /**
     * Sine of an angle given in degrees.
     */
    public static function sin(float $degrees): float
    {
        return sin(deg2rad($degrees));
    }

    /**
     * Cosine of an angle given in degrees.
     */
    public static function cos(float $degrees): float
    {
        return cos(deg2rad($degrees));
    }

    /**
     * Tangent of an angle given in degrees.
     */
    public static function tan(float $degrees): float
    {
        return tan(deg2rad($degrees));
    }

    /**
     * Arc-sine returning degrees.
     */
    public static function asin(float $value): float
    {
        return rad2deg(asin($value));
    }

    /**
     * Arc-cosine returning degrees.
     */
    public static function acos(float $value): float
    {
        return rad2deg(acos($value));
    }

    /**
     * Two-argument arc-tangent returning degrees in (-180, 180].
     */
    public static function atan2(float $y, float $x): float
    {
        return rad2deg(atan2($y, $x));
    }
}
