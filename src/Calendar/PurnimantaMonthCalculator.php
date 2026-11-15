<?php

declare(strict_types=1);

namespace Vittix\Panchang\Calendar;

use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Calculates the Purnimanta lunar month (North India: the month ends on
 * the full moon, Purnima).
 *
 * Correct rule (full moon to full moon):
 *
 *   A Purnimanta month runs from one full moon to the next and is named
 *   after the Amanta lunation that begins with the first new moon after
 *   its *starting* full moon — i.e. the Amanta lunation whose Shukla
 *   Paksha the Purnimanta month contains and ends with. Its Adhika
 *   status follows that same naming lunation.
 *
 * This handles Adhika months correctly, where the naive "Krishna Paksha
 * = Amanta month + 1" shortcut breaks down:
 *
 *   2023 (Adhika Shravana, Amanta 2023-07-18 … 2023-08-16):
 *     • 2023-07-25 (Shukla of the Adhika lunation) → Adhika Shravana.
 *     • 2023-08-05/10 (Krishna of the Adhika lunation) → nija Shravana —
 *       the +1 shortcut would wrongly give Bhadrapada.
 *     • 2023-07-10 (Krishna before the Adhika lunation) → Adhika Shravana.
 *
 * For Kshaya months the naming lunation skips a month name, so the
 * Purnimanta month may equal the Amanta month even in Krishna Paksha.
 */
final class PurnimantaMonthCalculator
{
    public function __construct(
        private readonly LunarMonthCalculator $lunarMonthCalc,
    ) {
    }

    /**
     * The Purnimanta month (name + Adhika/Kshaya status) containing the
     * given instant.
     *
     * The month started at the most recent full moon; the first new moon
     * after that full moon opens the Amanta lunation that names it.
     */
    public function monthAt(AstroInstant $instant): Lunation
    {
        $monthStartFullMoon = $this->lunarMonthCalc->previousFullMoon($instant);
        $namingNewMoon = $this->lunarMonthCalc->nextNewMoon($monthStartFullMoon);

        return $this->lunarMonthCalc->amantaLunation($namingNewMoon);
    }
}
