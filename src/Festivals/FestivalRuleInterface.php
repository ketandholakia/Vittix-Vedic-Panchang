<?php

declare(strict_types=1);

namespace Vittix\Panchang\Festivals;

use DateTimeImmutable;
use Vittix\Panchang\Panchanga\PanchangaCalculator;

/**
 * A rule that determines if a festival is observed on a given civil date.
 */
interface FestivalRuleInterface
{
    /**
     * Determine if this festival is observed on the given date.
     *
     * @param DateTimeImmutable $date The civil date (usually 00:00 local time).
     * @param \Vittix\Panchang\ValueObject\GeoLocation $location The observer's location.
     * @param PanchangaCalculator $panchanga The calculator to fetch planetary positions and tithis.
     * @param mixed $context Any additional context (like timeline calculators) can be passed if needed.
     */
    public function isObserved(DateTimeImmutable $date, \Vittix\Panchang\ValueObject\GeoLocation $location, PanchangaCalculator $panchanga, mixed $context = null): bool;
}
