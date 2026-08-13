<?php

declare(strict_types=1);

namespace Vittix\Panchang\Time;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Converts a wall-clock DateTimeImmutable into an AstroInstant that carries
 * both the UT Julian Day and the TT Julian Day (used by all astronomical
 * formulae).
 *
 * Flow:
 *   Local time → UTC → JD_UT → ΔT → JD_TT
 */
final class AstroTimeFactory
{
    private readonly DeltaTProviderInterface $deltaTProvider;

    public function __construct(?DeltaTProviderInterface $deltaTProvider = null)
    {
        $this->deltaTProvider = $deltaTProvider ?? new PolynomialDeltaTProvider();
    }

    public function fromDateTime(DateTimeImmutable $dateTime): AstroInstant
    {
        // Coerce to UTC first
        $utc = $dateTime->setTimezone(new DateTimeZone('UTC'));

        $julianDayUt = JulianDay::fromUtc($utc);

        $decimalYear = (float) $utc->format('Y')
            + ((int) $utc->format('z') / 365.25);

        $deltaTSeconds = $this->deltaTProvider->getDeltaT($decimalYear);

        // JD_TT = JD_UT + ΔT (in days)
        $julianDayTt = $julianDayUt + $deltaTSeconds / 86400.0;

        return new AstroInstant(
            utc: $utc,
            julianDayUt: $julianDayUt,
            julianDayTt: $julianDayTt,
            deltaTSeconds: $deltaTSeconds,
        );
    }

    /**
     * Convenience: create an AstroInstant from a raw JD_UT value.
     * Useful inside numerical solvers that iterate over Julian Days directly.
     */
    public function fromJulianDay(float $julianDayUt): AstroInstant
    {
        $utc = JulianDay::toUtc($julianDayUt);

        return $this->fromDateTime($utc);
    }
}
