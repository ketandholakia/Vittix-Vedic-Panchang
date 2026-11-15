<?php

declare(strict_types=1);

namespace Vittix\Panchang\Festivals;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Calendar\Lunation;
use Vittix\Panchang\Calendar\LunarMonthCalculator;

/**
 * Everything a festival rule needs for one civil date, computed once and
 * shared across all festival rules for that date.
 *
 * All solar events belong to the same civil date: $sunrise and $sunset are
 * on $date itself, $nextSunrise is the *real* sunrise of the following
 * date (never a "+1 day" approximation).
 */
final readonly class FestivalDayContext
{
    public function __construct(
        public readonly DateTimeImmutable $date,
        public readonly DateTimeZone $timezone,
        public readonly ?DateTimeImmutable $sunrise,
        public readonly ?DateTimeImmutable $sunset,
        public readonly ?DateTimeImmutable $nextSunrise,
        public readonly TithiTimeline $tithi,
        public readonly LunarMonthCalculator $lunarMonth,
        /**
         * Caller-verified classification of the lunation containing the
         * scored tithi span. When set, rules skip their own (expensive)
         * month re-verification — used by bounded-window resolutions where
         * the caller has already classified the lunation (e.g. the Samvat
         * new-year walk). The whole-year scan leaves it null.
         */
        public readonly ?Lunation $preverifiedLunation = null,
    ) {
    }
}
