<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

use DateTimeImmutable;

/**
 * Moonrise, lunar transit, and moonset times for a given date and location.
 *
 * All values are nullable because at extreme latitudes the Moon may not
 * rise or set on a given date. Additionally, the Moon's orbital period
 * (~24h 50min synodic) means it occasionally skips rising or setting
 * on a civil date.
 */
final readonly class LunarEvents
{
    public function __construct(
        public readonly ?DateTimeImmutable $moonrise,
        public readonly ?DateTimeImmutable $transit,
        public readonly ?DateTimeImmutable $moonset,
    ) {
    }

    /**
     * Whether the Moon rises on this date.
     */
    public function hasMoonrise(): bool
    {
        return $this->moonrise !== null;
    }

    /**
     * Whether the Moon sets on this date.
     */
    public function hasMoonset(): bool
    {
        return $this->moonset !== null;
    }

    /**
     * Duration the Moon is above the horizon in seconds.
     * Returns null if moonrise or moonset is missing.
     */
    public function durationAboveHorizonSeconds(): ?int
    {
        if ($this->moonrise === null || $this->moonset === null) {
            return null;
        }

        return $this->moonset->getTimestamp() - $this->moonrise->getTimestamp();
    }
}
