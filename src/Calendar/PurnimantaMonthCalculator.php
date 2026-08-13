<?php

declare(strict_types=1);

namespace Vittix\Panchang\Calendar;

use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Paksha;

/**
 * Calculates the Purnimanta lunar month.
 *
 * In the Purnimanta system (North India), the lunar month ends on the full moon (Purnima).
 * The waxing phase (Shukla Paksha) is identical to the Amanta system, but the waning phase
 * (Krishna Paksha) belongs to the *next* Amanta month.
 */
final class PurnimantaMonthCalculator
{
    /**
     * Get the Purnimanta month name based on the Amanta month and the current Paksha.
     */
    public function purnimantaMonth(LunarMonth $amantaMonth, Paksha $paksha): LunarMonth
    {
        // Shukla Paksha (waxing) is the same in both systems
        if ($paksha === Paksha::Shukla) {
            return $amantaMonth;
        }

        // Krishna Paksha (waning) precedes the Shukla Paksha of the same name in Purnimanta.
        // This means it inherits the name of the *upcoming* Amanta month.
        $nextMonthIndex = ($amantaMonth->value % 12) + 1;
        return LunarMonth::from($nextMonthIndex);
    }
}
