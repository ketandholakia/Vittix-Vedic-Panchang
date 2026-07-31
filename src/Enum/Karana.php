<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * The 11 Karanas.
 *
 * A Karana spans 6° of Moon–Sun elongation (half a Tithi).
 * There are 60 half-Tithis in a month, mapped to:
 *
 *   Half-Tithi  0        → Kimstughna  (fixed, occurs once at the very start)
 *   Half-Tithis 1–56     → 7 repeating Karanas × 8 rounds = 56 positions
 *                           order: Bava, Balava, Kaulava, Taitila, Garija, Vanija, Vishti
 *   Half-Tithi  57       → Shakuni     (fixed)
 *   Half-Tithi  58       → Chatushpada (fixed)
 *   Half-Tithi  59       → Naga        (fixed)
 *
 * Total unique Karanas: 4 fixed + 7 repeating = 11.
 */
enum Karana: int
{
    // Repeating (Chara) Karanas — indices used in the repeating sequence
    case Bava        = 0;
    case Balava      = 1;
    case Kaulava     = 2;
    case Taitila     = 3;
    case Garija      = 4;
    case Vanija      = 5;
    case Vishti      = 6;   // Bhadra — inauspicious

    // Fixed (Sthira) Karanas
    case Kimstughna  = 7;
    case Shakuni     = 8;
    case Chatushpada = 9;
    case Naga        = 10;

    /**
     * Determine the Karana for a given half-Tithi index (0–59).
     */
    public static function fromHalfTithiIndex(int $halfTithiIndex): self
    {
        $idx = $halfTithiIndex % 60;

        return match (true) {
            $idx === 0   => self::Kimstughna,
            $idx <= 56   => self::from(($idx - 1) % 7),
            $idx === 57  => self::Shakuni,
            $idx === 58  => self::Chatushpada,
            default      => self::Naga,
        };
    }

    public function isFixed(): bool
    {
        return $this->value >= 7;
    }

    public function isInauspicious(): bool
    {
        return $this === self::Vishti;
    }

    public function nameEnglish(): string
    {
        return match ($this) {
            self::Bava        => 'Bava',
            self::Balava      => 'Balava',
            self::Kaulava     => 'Kaulava',
            self::Taitila     => 'Taitila',
            self::Garija      => 'Garija',
            self::Vanija      => 'Vanija',
            self::Vishti      => 'Vishti (Bhadra)',
            self::Kimstughna  => 'Kimstughna',
            self::Shakuni     => 'Shakuni',
            self::Chatushpada => 'Chatushpada',
            self::Naga        => 'Naga',
        };
    }
}
