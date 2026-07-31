<?php

declare(strict_types=1);

namespace Vittix\Panchang\Festivals\Rules;

use DateTimeImmutable;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Festivals\FestivalRuleInterface;
use Vittix\Panchang\Muhurta\MuhurtaCalculator;
use Vittix\Panchang\Panchanga\PanchangaCalculator;
use Vittix\Panchang\ValueObject\TimeWindow;

/**
 * A rule that places a festival if a specific Tithi is active during a specific
 * Karmakala (time window like Madhyahna or Nishita).
 */
final class ViddhaTithiRule implements FestivalRuleInterface
{
    public function __construct(
        private readonly LunarMonth $targetMonth,
        private readonly Tithi $targetTithi,
        private readonly string $karmakalaName,
        private readonly MuhurtaCalculator $muhurtaCalculator
    ) {
    }

    public function isObserved(DateTimeImmutable $date, \Vittix\Panchang\ValueObject\GeoLocation $location, PanchangaCalculator $panchanga, mixed $context = null): bool
    {
        $record = $panchanga->forDay($date, $location);

        if ($record->lunarMonth !== $this->targetMonth) {
            return false;
        }
        
        $window = $this->getTimeWindow($record);
        if ($window === null) {
            return false;
        }

        // We need to check if the target Tithi is active at any point during this TimeWindow.
        // A simple way is to check if it's active at the midpoint of the window.
        // For a more precise check, we should see if the Tithi spans into the window.
        
        // Let's check the midpoint for now.
        $midpointTs = (int) (($window->start->getTimestamp() + $window->end->getTimestamp()) / 2);
        $midpoint = (new DateTimeImmutable())->setTimestamp($midpointTs)->setTimezone($window->start->getTimezone());
        
        $midpointRecord = $panchanga->forMoment($midpoint, $location);
        
        return $midpointRecord->tithi === $this->targetTithi;
    }

    private function getTimeWindow(\Vittix\Panchang\ValueObject\PanchangDay $record): ?TimeWindow
    {
        $sunrise = $record->solarEvents->sunrise;
        $sunset = $record->solarEvents->sunset;
        
        if ($sunrise === null || $sunset === null) {
            return null;
        }
        
        if ($this->karmakalaName === 'Nishita') {
            // Need next sunrise for night calculation. For simplicity here, we assume 24h later approx
            // In a real scenario, we'd pass nextSunrise properly or fetch it.
            $nextSunrise = $sunrise->modify('+1 day');
            return $this->muhurtaCalculator->getNishita($sunset, $nextSunrise);
        }

        $karmakalas = $this->muhurtaCalculator->getKarmakala($sunrise, $sunset);
        
        foreach ($karmakalas as $kk) {
            if ($kk->name === $this->karmakalaName) {
                return $kk;
            }
        }

        return null;
    }
}
