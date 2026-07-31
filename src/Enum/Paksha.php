<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * The two fortnights (Paksha) of the lunar month.
 *
 * Shukla Paksha: Tithis 1–15, from new moon to full moon (waxing).
 * Krishna Paksha: Tithis 1–15, from full moon to new moon (waning).
 */
enum Paksha: string
{
    case Shukla  = 'shukla';   // Bright fortnight (waxing)
    case Krishna = 'krishna';  // Dark fortnight  (waning)

    public function label(): string
    {
        return match ($this) {
            self::Shukla  => 'Shukla Paksha',
            self::Krishna => 'Krishna Paksha',
        };
    }


}
