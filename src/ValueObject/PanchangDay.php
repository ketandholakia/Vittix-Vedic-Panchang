<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Enum\Karana;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Nakshatra;
use Vittix\Panchang\Enum\Paksha;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Enum\Vara;
use Vittix\Panchang\Enum\Yoga;

/**
 * A complete sunrise-anchored daily Panchang.
 *
 * "Day" in the Hindu sense runs from one sunrise to the next, not midnight
 * to midnight.  All transition times are given as DateTimeImmutable values in
 * the requested timezone.
 */
final readonly class PanchangDay
{
    public function __construct(
        /** Civil date this Panchang is for. */
        public readonly DateTimeImmutable $date,
        public readonly DateTimeZone      $timezone,
        public readonly GeoLocation       $location,

        // Solar events
        public readonly SolarEvents       $solarEvents,

        // Panchang at sunrise
        public readonly Tithi             $tithiAtSunrise,
        public readonly Nakshatra         $nakshatraAtSunrise,
        public readonly int               $padaAtSunrise,
        public readonly Yoga              $yogaAtSunrise,
        public readonly Karana            $karanaAtSunrise,
        public readonly Vara              $vara,

        // End times for limbs that started before or at sunrise
        // Null means the limb runs through to the next sunrise (or beyond)
        public readonly ?DateTimeImmutable $tithiEndsAt,
        public readonly ?DateTimeImmutable $nakshatraEndsAt,
        public readonly ?DateTimeImmutable $yogaEndsAt,
        public readonly ?DateTimeImmutable $karanaEndsAt,

        // Second Tithi if two occur on this Panchang day (null otherwise)
        public readonly ?Tithi             $tithiSecond,
        public readonly ?DateTimeImmutable $tithiSecondEndsAt,

        // Calendar context
        public readonly ?LunarMonth        $lunarMonth,
        public readonly Paksha             $paksha,
        public readonly bool               $isAdhikaMasa,

        public readonly AccuracyInfo       $accuracy,
    ) {
    }
}
