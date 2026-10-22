<?php

declare(strict_types=1);

namespace Vittix\Panchang\Time;

use DateTimeImmutable;

/**
 * Shared fallback instant used when sunrise is undefined (polar day/night).
 *
 * Both PanchangaCalculator and PanchangService anchor their daily Panchang to
 * sunrise; where no sunrise exists for the local date, local noon is used as
 * the reference instant. This class keeps that convention in one place.
 */
final class SunriseFallback
{
    /** Local hour used as the reference instant when sunrise is undefined. */
    public const HOUR = 12;

    /**
     * Return the sunrise when defined, otherwise local noon of the given date.
     */
    public static function referenceTime(
        DateTimeImmutable $localDate,
        ?DateTimeImmutable $sunrise
    ): DateTimeImmutable {
        return $sunrise ?? $localDate->setTime(self::HOUR, 0, 0);
    }
}
