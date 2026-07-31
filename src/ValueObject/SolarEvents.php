<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

use DateTimeImmutable;

/**
 * Sunrise, solar noon, and sunset times for a given date and location.
 *
 * All values are nullable because at extreme latitudes (midnight sun or polar
 * night) some or all events may not occur.
 */
final readonly class SolarEvents
{
    public function __construct(
        public readonly ?DateTimeImmutable $sunrise,
        public readonly ?DateTimeImmutable $solarNoon,
        public readonly ?DateTimeImmutable $sunset,
    ) {
    }

    /**
     * Whether the Sun rises and sets on this date.
     */
    public function hasSunrise(): bool
    {
        return $this->sunrise !== null;
    }

    public function hasSunset(): bool
    {
        return $this->sunset !== null;
    }

    /**
     * Day length in seconds (null if sunrise or sunset is missing).
     */
    public function dayLengthSeconds(): ?int
    {
        if ($this->sunrise === null || $this->sunset === null) {
            return null;
        }

        return $this->sunset->getTimestamp() - $this->sunrise->getTimestamp();
    }
}
