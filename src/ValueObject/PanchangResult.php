<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

use DateTimeImmutable;
use Vittix\Panchang\Enum\Karana;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Nakshatra;
use Vittix\Panchang\Enum\Paksha;
use Vittix\Panchang\Enum\SolarMonth;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Enum\Vara;
use Vittix\Panchang\Enum\Yoga;

/**
 * Complete Panchang result for a date and location, designed for API consumption.
 *
 * Combines the five limbs of Panchang (Tithi, Nakshatra, Yoga, Karana, Vara)
 * with astronomical data (sunrise/sunset, moonrise/moonset), calendar context
 * (lunar month, samvat years, solar month), and accuracy metadata.
 *
 * This is the canonical output format for `PanchangService::getForDate()`.
 */
final readonly class PanchangResult
{
    public function __construct(
        // Date & Location
        public readonly DateTimeImmutable $date,
        public readonly GeoLocation $location,

        // Solar events
        public readonly SolarEvents $solarEvents,
        public readonly LunarEvents $lunarEvents,

        // Five limbs at sunrise
        public readonly Tithi $tithi,
        public readonly ?DateTimeImmutable $tithiEndsAt,
        public readonly ?Tithi $tithiSecond,
        public readonly ?DateTimeImmutable $tithiSecondEndsAt,

        public readonly Nakshatra $nakshatra,
        public readonly int $nakshatraPada,
        public readonly ?DateTimeImmutable $nakshatraEndsAt,

        public readonly Yoga $yoga,
        public readonly ?DateTimeImmutable $yogaEndsAt,

        public readonly Karana $karana,
        public readonly ?DateTimeImmutable $karanaEndsAt,

        public readonly Vara $vara,
        public readonly Paksha $paksha,

        // Calendar context
        public readonly ?LunarMonth $lunarMonth,
        public readonly bool $isAdhikaMasa,
        public readonly bool $isKshayaMasa,
        public readonly ?SolarMonth $solarMonth,
        public readonly int $vikramSamvat,
        public readonly int $shakaSamvat,

        // Astronomy
        public readonly float $ayanamsha,

        // Accuracy
        public readonly AccuracyInfo $accuracy,
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
            'location' => [
                'latitude' => round($this->location->latitude, 4),
                'longitude' => round($this->location->longitude, 4),
                'elevation' => $this->location->elevationMeters,
            ],
            'sunrise' => $this->solarEvents->sunrise?->format('H:i:s'),
            'sunset' => $this->solarEvents->sunset?->format('H:i:s'),
            'moonrise' => $this->lunarEvents->moonrise?->format('H:i:s'),
            'moonset' => $this->lunarEvents->moonset?->format('H:i:s'),
            'tithi' => [
                'name' => $this->tithi->nameEnglish(),
                'paksha' => $this->paksha->name,
                'endsAt' => $this->tithiEndsAt?->format('H:i:s'),
                'second' => $this->tithiSecond !== null ? [
                    'name' => $this->tithiSecond->nameEnglish(),
                    'endsAt' => $this->tithiSecondEndsAt?->format('H:i:s'),
                ] : null,
            ],
            'nakshatra' => [
                'name' => $this->nakshatra->nameEnglish(),
                'pada' => $this->nakshatraPada,
                'endsAt' => $this->nakshatraEndsAt?->format('H:i:s'),
            ],
            'yoga' => [
                'name' => $this->yoga->nameEnglish(),
                'endsAt' => $this->yogaEndsAt?->format('H:i:s'),
            ],
            'karana' => [
                'name' => $this->karana->nameEnglish(),
                'endsAt' => $this->karanaEndsAt?->format('H:i:s'),
            ],
            'vara' => $this->vara->nameEnglish(),
            'lunarMonth' => $this->lunarMonth?->nameEnglish(),
            'isAdhikaMasa' => $this->isAdhikaMasa,
            'isKshayaMasa' => $this->isKshayaMasa,
            'solarMonth' => $this->solarMonth?->nameEnglish(),
            'samvat' => [
                'vikram' => $this->vikramSamvat,
                'shaka' => $this->shakaSamvat,
            ],
            'ayanamsha' => round($this->ayanamsha, 4),
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
