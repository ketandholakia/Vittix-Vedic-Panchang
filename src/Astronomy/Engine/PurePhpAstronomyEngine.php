<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Engine;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Astronomy\Contracts\AyanamshaCalculatorInterface;
use Psr\SimpleCache\CacheInterface;
use Vittix\Panchang\Astronomy\Coordinates\NutationCalculator;
use Vittix\Panchang\Astronomy\Coordinates\ObliquityCalculator;
use Vittix\Panchang\Astronomy\Lunar\LunarArgumentCalculator;
use Vittix\Panchang\Astronomy\Lunar\LunarEventCalculator;
use Vittix\Panchang\Astronomy\Lunar\LunarPositionCalculator;
use Vittix\Panchang\Astronomy\Sidereal\LahiriAyanamsha;
use Vittix\Panchang\Astronomy\Sidereal\SiderealTimeCalculator;
use Vittix\Panchang\Astronomy\Planetary\EarthPositionCalculator;
use Vittix\Panchang\Astronomy\Planetary\MercuryTerms;
use Vittix\Panchang\Astronomy\Planetary\VenusTerms;
use Vittix\Panchang\Astronomy\Planetary\MarsTerms;
use Vittix\Panchang\Astronomy\Planetary\JupiterTerms;
use Vittix\Panchang\Astronomy\Planetary\SaturnTerms;
use Vittix\Panchang\Astronomy\Planetary\PlanetPositionCalculator;
use Vittix\Panchang\Astronomy\Solar\SolarEventCalculator;
use Vittix\Panchang\Astronomy\Solar\SolarPositionCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\ValueObject\EclipticPosition;
use Vittix\Panchang\ValueObject\EquatorialPosition;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\ValueObject\LunarEvents;
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
 * Cache strategy: position results are memoised per (body, JD_TT) string key.
 * Positions are geocentric, so the keys are location-independent by nature;
 * the ayanamsha is fixed per engine instance (one AyanamshaCalculatorInterface).
 * Both in-memory caches are bounded LRU caches (default 512 entries) so that
 * long-running batch generation cannot grow without limit; evicted entries are
 * recomputed (or re-fetched from the optional PSR-16 cache). The in-memory
 * caches are not persisted.
 */
final class PurePhpAstronomyEngine implements AstronomyEngineInterface
{
    /** Default bound for each in-memory LRU cache (positions, ayanamsha). */
    public const DEFAULT_MEMORY_CACHE_CAPACITY = 512;

    private readonly SolarPositionCalculator $solarCalculator;
    private readonly LunarPositionCalculator $lunarCalculator;
    private readonly PlanetPositionCalculator $mercuryCalculator;
    private readonly PlanetPositionCalculator $venusCalculator;
    private readonly PlanetPositionCalculator $marsCalculator;
    private readonly PlanetPositionCalculator $jupiterCalculator;
    private readonly PlanetPositionCalculator $saturnCalculator;
    private readonly SolarEventCalculator    $solarEvents;
    private readonly LunarEventCalculator    $lunarEvents;
    private readonly SiderealTimeCalculator  $siderealTimeCalc;
    private readonly NutationCalculator      $nutation;
    private readonly ObliquityCalculator     $obliquity;

    /**
     * Bounded LRU cache (oldest first); entries moved to the end on hit.
     *
     * @var array<string, EclipticPosition>
     */
    private array $positionCache = [];

    /**
     * Bounded LRU cache (oldest first); entries moved to the end on hit.
     *
     * @var array<string, float>
     */
    private array $ayanamshaCache = [];

    private readonly int $memoryCacheCapacity;

    private readonly LunarArgumentCalculator $lunarArguments;

    public function __construct(
        private readonly AyanamshaCalculatorInterface $ayanamsha = new LahiriAyanamsha(),
        private readonly ?CacheInterface $cache = null,
        int $memoryCacheCapacity = self::DEFAULT_MEMORY_CACHE_CAPACITY,
    ) {
        if ($memoryCacheCapacity < 1) {
            throw new \InvalidArgumentException('Memory cache capacity must be at least 1.');
        }
        $this->memoryCacheCapacity = $memoryCacheCapacity;
        $this->nutation       = new NutationCalculator();
        $this->obliquity      = new ObliquityCalculator($this->nutation);
        $this->solarCalculator = new SolarPositionCalculator($this->nutation, $this->obliquity);
        
        $earth = new EarthPositionCalculator();
        $this->mercuryCalculator = new PlanetPositionCalculator(MercuryTerms::class, $earth, $this->nutation);
        $this->venusCalculator = new PlanetPositionCalculator(VenusTerms::class, $earth, $this->nutation);
        $this->marsCalculator = new PlanetPositionCalculator(MarsTerms::class, $earth, $this->nutation);
        $this->jupiterCalculator = new PlanetPositionCalculator(JupiterTerms::class, $earth, $this->nutation);
        $this->saturnCalculator = new PlanetPositionCalculator(SaturnTerms::class, $earth, $this->nutation);

        $this->lunarArguments  = new LunarArgumentCalculator();
        $this->lunarCalculator = new LunarPositionCalculator(
            $this->lunarArguments,
            $this->nutation,
        );
        $this->solarEvents = new SolarEventCalculator($this->solarCalculator);
        $this->siderealTimeCalc = new SiderealTimeCalculator();
        // LunarEventCalculator no longer depends on $this, so it can be fully initialized here.
        $this->lunarEvents = new LunarEventCalculator($this->lunarCalculator, $this->obliquity, $this->siderealTimeCalc);
    }

    public function position(Body $body, AstroInstant $instant): EclipticPosition
    {
        $key = $this->positionKey($body, $instant);

        if (array_key_exists($key, $this->positionCache)) {
            return $this->cacheTouch($this->positionCache, $key);
        }

        if ($this->cache !== null && $this->cache->has($key)) {
            $position = $this->cache->get($key);
            $this->cacheStore($this->positionCache, $key, $position);

            return $position;
        }

        $position = match ($body) {
            Body::Sun      => $this->solarCalculator->calculate($instant),
            Body::Moon     => $this->lunarCalculator->calculate($instant),
            Body::Mercury  => $this->mercuryCalculator->calculate($instant),
            Body::Venus    => $this->venusCalculator->calculate($instant),
            Body::Mars     => $this->marsCalculator->calculate($instant),
            Body::Jupiter  => $this->jupiterCalculator->calculate($instant),
            Body::Saturn   => $this->saturnCalculator->calculate($instant),
            Body::RahuMean => new EclipticPosition(
                longitude: $this->lunarArguments->calculate($instant)['omega'],
                latitude: 0.0,
                distance: 0.0,
            ),
            Body::KetuMean => new EclipticPosition(
                longitude: Angle::normalize(
                    $this->lunarArguments->calculate($instant)['omega'] + 180.0
                ),
                latitude: 0.0,
                distance: 0.0,
            ),
        };

        $this->cacheStore($this->positionCache, $key, $position);
        if ($this->cache !== null) {
            $this->cache->set($key, $position);
        }

        return $position;
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
        // Add prefix to avoid collision with position keys (though structurally different)
        $key = 'ayan:jd:' . sprintf('%.6F', $instant->julianDayTt);

        if (array_key_exists($key, $this->ayanamshaCache)) {
            return $this->cacheTouch($this->ayanamshaCache, $key);
        }

        if ($this->cache !== null && $this->cache->has($key)) {
            $ayanamsa = $this->cache->get($key);
            $this->cacheStore($this->ayanamshaCache, $key, $ayanamsa);

            return $ayanamsa;
        }

        $ayanamsa = $this->ayanamsha->calculate($instant);

        $this->cacheStore($this->ayanamshaCache, $key, $ayanamsa);
        if ($this->cache !== null) {
            $this->cache->set($key, $ayanamsa);
        }

        return $ayanamsa;
    }

    public function equatorialPosition(Body $body, AstroInstant $instant): EquatorialPosition
    {
        $ecliptic = $this->position($body, $instant);
        $obliquity = $this->obliquity->true($instant);

        $transformer = new \Vittix\Panchang\Astronomy\Coordinates\CoordinateTransformer();
        return $transformer->eclipticToEquatorial($ecliptic, $obliquity);
    }

    public function dailyMotion(Body $body, AstroInstant $instant): float
    {
        $deltaDays = 0.001; // ~1.44 minutes

        $pos1 = $this->tropicalLongitude($body, $instant);

        $nextInstant = new AstroInstant(
            $instant->utc->modify('+86 seconds'),
            $instant->julianDayUt + $deltaDays,
            $instant->julianDayTt + $deltaDays,
            $instant->deltaTSeconds
        );

        $pos2 = $this->tropicalLongitude($body, $nextInstant);

        // Handle crossing the 360 degree boundary
        $diff = Angle::normalize($pos2 - $pos1);
        if ($diff > 180.0) {
            $diff -= 360.0;
        }

        // Return degrees per day
        return $diff / $deltaDays;
    }

    public function solarEvents(
        DateTimeImmutable $localDate,
        GeoLocation $location,
        DateTimeZone $timezone
    ): SolarEvents {
        return $this->solarEvents->calculate($localDate, $location, $timezone);
    }

    public function lunarEvents(
        DateTimeImmutable $localDate,
        GeoLocation $location,
        DateTimeZone $timezone
    ): LunarEvents {
        return $this->lunarEvents->calculate($localDate, $location, $timezone);
    }

    public function siderealTime(
        AstroInstant $instant,
        GeoLocation $location
    ): float {
        return $this->siderealTimeCalc->localMeanSiderealTime(
            $instant->julianDayUt,
            $location->longitude
        );
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

    /**
     * Move an existing entry to the most-recently-used end and return it.
     *
     * @template T
     * @param array<string, T> $cache
     * @return T
     */
    private function cacheTouch(array &$cache, string $key): mixed
    {
        $value = $cache[$key];
        unset($cache[$key]);
        $cache[$key] = $value;

        return $value;
    }

    /**
     * Insert an entry at the most-recently-used end, evicting the least
     * recently used entries beyond the capacity bound.
     *
     * @template T
     * @param array<string, T> $cache
     * @param T $value
     */
    private function cacheStore(array &$cache, string $key, mixed $value): void
    {
        unset($cache[$key]);
        $cache[$key] = $value;

        while (count($cache) > $this->memoryCacheCapacity) {
            $oldest = array_key_first($cache);
            unset($cache[$oldest]);
        }
    }

    private function positionKey(Body $body, AstroInstant $instant): string
    {
        return sprintf('pos:%s:%.8F', $body->value, $instant->julianDayTt);
    }
}
