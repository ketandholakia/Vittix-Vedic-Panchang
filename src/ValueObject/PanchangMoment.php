<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

use DateTimeImmutable;
use Vittix\Panchang\Enum\Karana;
use Vittix\Panchang\Enum\Nakshatra;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Enum\Vara;
use Vittix\Panchang\Enum\Yoga;

/**
 * All five Panchang limbs calculated for a specific instant in time.
 *
 * "Moment" means: what are the five Angas right now (or at the queried instant)?
 * For a "daily" view that includes start/end times for each limb, use PanchangDay.
 */
final readonly class PanchangMoment
{
    public function __construct(
        public readonly AstroInstant  $instant,
        public readonly Tithi         $tithi,
        public readonly Nakshatra     $nakshatra,
        public readonly int           $pada,          // 1–4
        public readonly Yoga          $yoga,
        public readonly Karana        $karana,
        public readonly Vara          $vara,
        public readonly float         $sunLongitude,   // tropical, degrees
        public readonly float         $moonLongitude,  // tropical, degrees
        public readonly float         $ayanamsha,      // degrees
        public readonly AccuracyInfo  $accuracy,
    ) {
    }
}
