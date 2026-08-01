<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

use DateTimeImmutable;
use Vittix\Panchang\Enum\Body;

/**
 * Represents a planetary time period (Dasha or Antardasha).
 */
final class DashaPeriod
{
    /**
     * @param Body $body The ruling planet of this period.
     * @param DateTimeImmutable $start The start date and time of the period.
     * @param DateTimeImmutable $end The end date and time of the period.
     * @param array<DashaPeriod> $subPeriods The sub-periods (Antardashas) within this period.
     */
    public function __construct(
        public readonly Body $body,
        public readonly DateTimeImmutable $start,
        public readonly DateTimeImmutable $end,
        public readonly array $subPeriods = [],
    ) {
    }
}
