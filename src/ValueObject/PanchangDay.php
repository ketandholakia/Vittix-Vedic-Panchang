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

    /**
     * Convert to a structured array suitable for JSON encoding.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date->format('Y-m-d'),
            'timezone' => $this->timezone->getName(),
            'location' => [
                'latitude' => round($this->location->latitude, 4),
                'longitude' => round($this->location->longitude, 4),
                'elevation' => $this->location->elevationMeters,
            ],
            'sunrise' => $this->solarEvents->sunrise?->format('H:i:s'),
            'solarNoon' => $this->solarEvents->solarNoon?->format('H:i:s'),
            'sunset' => $this->solarEvents->sunset?->format('H:i:s'),
            'tithi' => [
                'name' => $this->tithiAtSunrise->nameEnglish(),
                'paksha' => $this->paksha->name,
                'endsAt' => $this->tithiEndsAt?->format('H:i:s'),
            ],
            'tithiSecond' => $this->tithiSecond !== null ? [
                'name' => $this->tithiSecond->nameEnglish(),
                'endsAt' => $this->tithiSecondEndsAt?->format('H:i:s'),
            ] : null,
            'nakshatra' => [
                'name' => $this->nakshatraAtSunrise->nameEnglish(),
                'pada' => $this->padaAtSunrise,
                'endsAt' => $this->nakshatraEndsAt?->format('H:i:s'),
            ],
            'yoga' => [
                'name' => $this->yogaAtSunrise->nameEnglish(),
                'endsAt' => $this->yogaEndsAt?->format('H:i:s'),
            ],
            'karana' => [
                'name' => $this->karanaAtSunrise->nameEnglish(),
                'endsAt' => $this->karanaEndsAt?->format('H:i:s'),
            ],
            'vara' => $this->vara->nameEnglish(),
            'lunarMonth' => $this->lunarMonth?->nameEnglish(),
            'isAdhikaMasa' => $this->isAdhikaMasa,
            'accuracy' => [
                'nearBoundary' => $this->accuracy->nearBoundary,
                'estimatedErrorDegrees' => $this->accuracy->estimatedAngularErrorDegrees,
                'estimatedErrorSeconds' => $this->accuracy->estimatedTimeErrorSeconds,
            ],
        ];
    }

    /**
     * Serialize to JSON.
     */
    public function toJson(int $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArray(), $options) ?: '{}';
    }
}
