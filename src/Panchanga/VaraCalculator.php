<?php

declare(strict_types=1);

namespace Vittix\Panchang\Panchanga;

use DateTimeImmutable;
use Vittix\Panchang\Enum\Vara as VaraEnum;

/**
 * Vara (weekday) calculator.
 *
 * In Hindu Panchang, Vara runs from sunrise to the next sunrise — not from
 * civil midnight to midnight.  A moment occurring before today's sunrise
 * belongs to yesterday's Vara.
 *
 * When sunrise is available, pass it to calculate().  When sunrise is null
 * (polar edge case), fall back to the civil weekday of the moment.
 */
final class VaraCalculator
{
    /**
     * Calculate the Panchang Vara (weekday) for a given moment.
     *
     * @param DateTimeImmutable      $moment  The query moment (local time).
     * @param DateTimeImmutable|null $sunrise Today's sunrise in the same timezone.
     */
    public function calculate(
        DateTimeImmutable $moment,
        ?DateTimeImmutable $sunrise
    ): VaraEnum {
        // If the moment is before sunrise, it belongs to yesterday's Vara.
        if ($sunrise !== null && $moment < $sunrise) {
            $panchangDay = $moment->modify('-1 day');
        } else {
            $panchangDay = $moment;
        }

        $isoWeekday = (int) $panchangDay->format('N'); // 1=Mon … 7=Sun

        return VaraEnum::fromIsoWeekday($isoWeekday);
    }
}
