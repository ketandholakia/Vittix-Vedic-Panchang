<?php

declare(strict_types=1);

namespace Vittix\Panchang\Festivals;

use DateTimeImmutable;
use Vittix\Panchang\Panchanga\PanchangaCalculator;

final class FestivalEngine
{
    /** @var Festival[] */
    private array $catalog = [];

    public function __construct(
        private readonly PanchangaCalculator $calculator
    ) {
    }

    public function addFestival(Festival $festival): void
    {
        $this->catalog[] = $festival;
    }

    /**
     * Get all festivals observed on the given date.
     *
     * @return string[] List of festival names.
     */
    public function getFestivalsForDate(DateTimeImmutable $date, \Vittix\Panchang\ValueObject\GeoLocation $location): array
    {
        $observed = [];

        foreach ($this->catalog as $festival) {
            if ($festival->rule->isObserved($date, $location, $this->calculator)) {
                $observed[] = $festival->name;
            }
        }

        return $observed;
    }
}
