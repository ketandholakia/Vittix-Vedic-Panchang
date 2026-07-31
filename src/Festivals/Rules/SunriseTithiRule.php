<?php

declare(strict_types=1);

namespace Vittix\Panchang\Festivals\Rules;

use DateTimeImmutable;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Festivals\FestivalRuleInterface;
use Vittix\Panchang\Panchanga\PanchangaCalculator;

/**
 * A simple rule that places a festival if a specific Tithi and Lunar Month
 * are active at Sunrise on the given date.
 */
final class SunriseTithiRule implements FestivalRuleInterface
{
    public function __construct(
        private readonly LunarMonth $targetMonth,
        private readonly Tithi $targetTithi
    ) {
    }

    public function isObserved(DateTimeImmutable $date, \Vittix\Panchang\ValueObject\GeoLocation $location, PanchangaCalculator $panchanga, mixed $context = null): bool
    {
        // Ideally we check if the lunar month and tithi match at sunrise.
        // For now, we use the PanchangaCalculator's basic calculate method which currently
        // returns the Tithi and Month at the start of the day or at sunrise if configured.
        
        // $panchanga->forDay returns a PanchangDay record.
        $record = $panchanga->forDay($date, $location);

        // Check if both Tithi and Lunar Month match our targets
        return $record->tithiAtSunrise === $this->targetTithi && $record->lunarMonth === $this->targetMonth;
    }
}
