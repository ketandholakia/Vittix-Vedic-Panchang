<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Engine;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Astronomy\Contracts\AyanamshaCalculatorInterface;
use Vittix\Panchang\Astronomy\Coordinates\NutationCalculator;
use Vittix\Panchang\Astronomy\Coordinates\ObliquityCalculator;
use Vittix\Panchang\Astronomy\Lunar\LunarArgumentCalculator;
use Vittix\Panchang\Astronomy\Lunar\LunarPositionCalculator;
use Vittix\Panchang\Astronomy\Sidereal\LahiriAyanamsha;
use Vittix\Panchang\Astronomy\Solar\SolarEventCalculator;
use Vittix\Panchang\Astronomy\Solar\SolarPositionCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\ValueObject\EclipticPosition;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\ValueObject\SolarEvents;

/**
 * Pure-PHP astronomy engine implementing AstronomyEngineInterface.
 *
 * This is the main entry point for all astronomical calculations.
 * It composes the Solar, Lunar, Nutation, Obliquity, Ayanamsha, and
 * SolarEvent calculators into a single, cache-aware facade.
 *
 * All calculations use only PHP built-in math functions. No extensions,
 * no FFI, no external libraries.
 *
 * Cache strategy: position results are memoised per (body, JD_TT) string key
 * within a single request/calculation cycle. The cache is not persisted.
 */
final class PurePhpAstronomyEngine implements AstronomyEngineInterface
{
    private readonly SolarPositionCalculator $solarCalculator;
    private readonly LunarPositionCalculator $lunarCalculator;
    private readonly SolarEventCalculator    $solarEvents;
    private readonly NutationCalculator      $nutation;
    private readonly ObliquityCalculator     $obliquity;

    /** @var array<string, EclipticPosition> */
    private array $positionCache = [];

    /** @var array<string, float> */
    private array $ayanamshaCache = [];

    public function __construct(
        private readonly AyanamshaCalculatorInterface $ayanamsha = new LahiriAyanamsha(),
    ) {
        $this->nutation       = new NutationCalculator();
        $this->obliquity      = new ObliquityCalculator($this->nutation);
        $this->solarCalculator = new SolarPositionCalculator($this->nutation, $this->obliquity);
        $this->lunarCalculator = new LunarPositionCalculator(
            new LunarArgumentCalculator(),
            $this->nutation,
            $this->obliquity,
        );
        $this->solarEvents = new SolarEventCalculator($this->solarCalculator);
    }

    public function position(Body $body, AstroInstant $instant): EclipticPosition
    {
        $key = $this->positionKey($body, $instant);

        if (!isset($this->positionCache[$key])) {
            $this->positionCache[$key] = match ($body) {
                Body::Sun  => $this->solarCalculator->calculate($instant),
                Body::Moon => $this->lunarCalculator->calculate($instant),
            };
        }

        return $this->positionCache[$key];
    }

    public function tropicalLongitude(Body $body, AstroInstant $instant): float
    {
        return $this->position($body, $instant)->longitude;
    }

    public function siderealLongitude(Body $body, AstroInstant $instant): float
    {
        return Angle::normalize(
            $this->tropicalLongitude($body, $instant) - $this->ayanamsa($instant)
        );
    }

    public function ayanamsa(AstroInstant $instant): float
    {
        $key = sprintf('%.6F', $instant->julianDayTt);

        if (!isset($this->ayanamshaCache[$key])) {
            $this->ayanamshaCache[$key] = $this->ayanamsha->calculate($instant);
        }

        return $this->ayanamshaCache[$key];
    }

    public function solarEvents(
        DateTimeImmutable $localDate,
        GeoLocation $location,
        DateTimeZone $timezone
    ): SolarEvents {
        return $this->solarEvents->calculate($localDate, $location, $timezone);
    }

    /**
     * Clear the position and ayanamsha caches.
     * Useful in long-running processes (e.g. batch calendar generation).
     */
    public function clearCache(): void
    {
        $this->positionCache  = [];
        $this->ayanamshaCache = [];
    }

    private function positionKey(Body $body, AstroInstant $instant): string
    {
        return sprintf('%s|%.8F', $body->value, $instant->julianDayTt);
    }
}
