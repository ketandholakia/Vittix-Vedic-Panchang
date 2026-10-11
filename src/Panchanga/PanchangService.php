<?php

declare(strict_types=1);

namespace Vittix\Panchang\Panchanga;

use DateTimeImmutable;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Calendar\HinduCalendarCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\SolarMonth;
use Vittix\Panchang\Enum\MonthSystem;
use Vittix\Panchang\Enum\AyanamshaSystem;
use Psr\SimpleCache\CacheInterface;
use Vittix\Panchang\Festivals\FestivalEngine;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\ValueObject\AstronomyResult;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\ValueObject\PanchangResult;

/**
 * Clean public entry point for Panchang calculations.
 *
 * This service composes the astronomy engine, panchanga calculator, and
 * calendar calculator into structured, JSON-ready outputs. It is the
 * canonical API surface for Phase 5 (REST API) and Phase 6 (CLI).
 *
 * The default factory shares the object graph of the {@see Panchang} facade
 * (one engine, one set of calculators); the astrology features of the facade
 * are reachable through the pass-through methods below.
 *
 * Usage:
 *
 *   $service = PanchangService::createDefault();
 *
 *   $result = $service->getForDate(
 *       new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata')),
 *       new GeoLocation(latitude: 23.0225, longitude: 72.5714)
 *   );
 *
 *   echo $result->toJson();
 */
final class PanchangService
{
    /** Bound of the in-memory result LRU cache (per service instance). */
    public const RESULT_CACHE_CAPACITY = 512;

    private readonly AstroTimeFactory $timeFactory;

    /** Lazily shared facade, always present for factories; built on demand for the raw constructor. */
    private ?Panchang $panchang = null;

    /**
     * Bounded LRU cache of computed results (PanchangResult / AstronomyResult),
     * keyed by result kind + date + timezone + location (+ month system).
     * Oldest entries first; moved to the end on hit; evicted beyond capacity.
     *
     * @var array<string, object>
     */
    private array $resultCache = [];

    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
        private readonly PanchangaCalculator $panchangaCalculator,
        private readonly HinduCalendarCalculator $calendarCalculator,
    ) {
        $this->timeFactory = new AstroTimeFactory();
    }

    /**
     * Create a PanchangService that shares the object graph of an existing
     * Panchang facade — a single engine and a single set of calculators are
     * constructed, and both entry points delegate to them.
     */
    public static function fromPanchang(Panchang $panchang): self
    {
        $service = new self(
            $panchang->engine(),
            $panchang->calculator(),
            $panchang->calendarCalculator(),
        );
        $service->panchang = $panchang;

        return $service;
    }

    /**
     * Create a PanchangService with default configuration.
     *
     * This is a convenience factory that creates the standard pure-PHP Lahiri
     * engine graph once (via the Panchang facade) and shares it.
     */
    public static function createDefault(
        AyanamshaSystem $ayanamshaSystem = AyanamshaSystem::Lahiri,
        ?CacheInterface $cache = null
    ): self {
        return self::fromPanchang(
            Panchang::createDefault($ayanamshaSystem, $cache)
        );
    }

    /**
     * The shared Panchang facade backing the astrology pass-throughs.
     * Built lazily (once) when the service was constructed from raw parts.
     */
    private function panchang(): Panchang
    {
        return $this->panchang ??= Panchang::create($this->astronomy);
    }

    /**
     * Calculate the complete Panchang for a specific date and location.
     *
     * This is the main public method. Returns a PanchangResult that can
     * be serialized to JSON via `$result->toJson()` or inspected directly.
     *
     * Results are memoised in a bounded LRU cache keyed by date, timezone,
     * location and month system; repeat calls with identical inputs are
     * served from memory. Call {@see clearCache()} to force recomputation.
     */
    public function getForDate(
        DateTimeImmutable $date,
        GeoLocation $location,
        MonthSystem $monthSystem = MonthSystem::Amanta
    ): PanchangResult {
        $key = $this->resultCacheKey('day', $date, $location) . '|' . $monthSystem->name;

        $cached = $this->cacheGet($key);
        if ($cached instanceof PanchangResult) {
            return $cached;
        }

        $timezone = $date->getTimezone();

        // Calculate the full PanchangDay (sunrise-anchored)
        $panchangDay = $this->panchangaCalculator->forDay($date, $location, $monthSystem);

        // Lunar events (moonrise/moonset)
        $lunarEvents = $this->astronomy->lunarEvents($date, $location, $timezone);

        // Calendar context (Samvat, Adhika Masa, solar month)
        $instant = $this->timeFactory->fromDateTime(
            $panchangDay->solarEvents->sunrise ?? $date->setTime(12, 0, 0)
        );
        $calendarResult = $this->calendarCalculator->calculate($instant, $timezone);

        // Solar month from Sun's sidereal longitude
        $sunSiderealLon = $this->astronomy->siderealLongitude(Body::Sun, $instant);
        $solarMonth = SolarMonth::fromSiderealLongitude($sunSiderealLon);

        $result = new PanchangResult(
            date: $date,
            location: $location,
            solarEvents: $panchangDay->solarEvents,
            lunarEvents: $lunarEvents,
            tithi: $panchangDay->tithiAtSunrise,
            tithiEndsAt: $panchangDay->tithiEndsAt,
            tithiSecond: $panchangDay->tithiSecond,
            tithiSecondEndsAt: $panchangDay->tithiSecondEndsAt,
            nakshatra: $panchangDay->nakshatraAtSunrise,
            nakshatraPada: $panchangDay->padaAtSunrise,
            nakshatraEndsAt: $panchangDay->nakshatraEndsAt,
            yoga: $panchangDay->yogaAtSunrise,
            yogaEndsAt: $panchangDay->yogaEndsAt,
            karana: $panchangDay->karanaAtSunrise,
            karanaEndsAt: $panchangDay->karanaEndsAt,
            vara: $panchangDay->vara,
            paksha: $panchangDay->paksha,
            lunarMonth: $panchangDay->lunarMonth,
            isAdhikaMasa: $panchangDay->isAdhikaMasa,
            isKshayaMasa: $panchangDay->isKshayaMasa,
            solarMonth: $solarMonth,
            vikramSamvat: $calendarResult->vikramSamvat,
            shakaSamvat: $calendarResult->shakaSamvat,
            ayanamsha: $this->astronomy->ayanamsa($instant),
            accuracy: $panchangDay->accuracy,
        );

        $this->cacheSet($key, $result);

        return $result;
    }

    /**
     * Clear the service-level result cache and the engine's in-memory caches.
     *
     * Useful in long-running processes that prefer to reclaim memory at
     * explicit points instead of relying on the bounded LRU eviction.
     */
    public function clearCache(): void
    {
        $this->resultCache = [];

        if (method_exists($this->astronomy, 'clearCache')) {
            $this->astronomy->clearCache();
        }
    }

    /**
     * Get a pure astronomy report (no Panchang logic) for a date and location.
     *
     * This is the "AstronomyResult" output — solar events, lunar events,
     * ayanamsha, sidereal time, and longitudes without any calendar or
     * Panchang overlay. Results are memoised like {@see getForDate()}.
     */
    public function getAstronomyReport(
        DateTimeImmutable $date,
        GeoLocation $location
    ): AstronomyResult {
        $key = $this->resultCacheKey('astro', $date, $location);

        $cached = $this->cacheGet($key);
        if ($cached instanceof AstronomyResult) {
            return $cached;
        }

        $timezone = $date->getTimezone();

        $solarEvents = $this->astronomy->solarEvents($date, $location, $timezone);
        $lunarEvents = $this->astronomy->lunarEvents($date, $location, $timezone);

        // Use sunrise (or noon) as reference instant
        $referenceTime = $solarEvents->sunrise ?? $date->setTime(12, 0, 0);
        $instant = $this->timeFactory->fromDateTime($referenceTime);

        $ayanamsha = $this->astronomy->ayanamsa($instant);
        $siderealTime = $this->astronomy->siderealTime($instant, $location);
        $sunSiderealLon = $this->astronomy->siderealLongitude(Body::Sun, $instant);
        $moonSiderealLon = $this->astronomy->siderealLongitude(Body::Moon, $instant);
        $solarMonth = SolarMonth::fromSiderealLongitude($sunSiderealLon);

        $result = new AstronomyResult(
            date: $date,
            location: $location,
            solarEvents: $solarEvents,
            lunarEvents: $lunarEvents,
            ayanamsha: $ayanamsha,
            siderealTime: $siderealTime,
            sunSiderealLongitude: $sunSiderealLon,
            moonSiderealLongitude: $moonSiderealLon,
            solarMonth: $solarMonth,
        );

        $this->cacheSet($key, $result);

        return $result;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build a cache key that fully determines the computation inputs:
     * result kind, date (with offset), timezone name, and the full location
     * (latitude, longitude, elevation). The engine configuration is constant
     * per service instance, so it does not need to enter the key.
     */
    private function resultCacheKey(
        string $kind,
        DateTimeImmutable $date,
        GeoLocation $location
    ): string {
        return sprintf(
            '%s|%s|%s|%.6F,%.6F,%.2F',
            $kind,
            $date->format('c'),
            $date->getTimezone()->getName(),
            $location->latitude,
            $location->longitude,
            $location->elevationMeters,
        );
    }

    /**
     * Fetch a cached result, moving it to the most-recently-used end.
     */
    private function cacheGet(string $key): ?object
    {
        if (!array_key_exists($key, $this->resultCache)) {
            return null;
        }

        $value = $this->resultCache[$key];
        unset($this->resultCache[$key]);
        $this->resultCache[$key] = $value;

        return $value;
    }

    /**
     * Insert a result at the most-recently-used end, evicting the least
     * recently used entries beyond {@see RESULT_CACHE_CAPACITY}.
     */
    private function cacheSet(string $key, object $value): void
    {
        unset($this->resultCache[$key]);
        $this->resultCache[$key] = $value;

        while (count($this->resultCache) > self::RESULT_CACHE_CAPACITY) {
            $oldest = array_key_first($this->resultCache);
            unset($this->resultCache[$oldest]);
        }
    }

    // -------------------------------------------------------------------------
    // Facade pass-throughs (astrology features)
    //
    // Thin one-line delegations to the shared Panchang facade so the service
    // is a complete REST-API surface. Each method matches the facade signature.
    // -------------------------------------------------------------------------

    /**
     * Calculate the sunrise-anchored daily Panchang for a date and location.
     */
    public function day(DateTimeImmutable $date, GeoLocation $location): \Vittix\Panchang\ValueObject\PanchangDay
    {
        return $this->panchang()->day($date, $location);
    }

    /**
     * Calculate all five Panchang limbs for a specific moment and location.
     */
    public function moment(DateTimeImmutable $moment, GeoLocation $location): \Vittix\Panchang\ValueObject\PanchangMoment
    {
        return $this->panchang()->moment($moment, $location);
    }

    /**
     * Calculate the Janmarashi (Moon sign) at a specific moment.
     */
    public function janmarashi(DateTimeImmutable $moment): \Vittix\Panchang\Enum\Rashi
    {
        return $this->panchang()->janmarashi($moment);
    }

    /**
     * Get the FestivalEngine pre-configured with default festivals.
     */
    public function festivals(): FestivalEngine
    {
        return $this->panchang()->festivals();
    }

    /**
     * Get a MuhurtaCalculator instance.
     */
    public function muhurta(): \Vittix\Panchang\Muhurta\MuhurtaCalculator
    {
        return $this->panchang()->muhurta();
    }

    /**
     * Get an ElectionalEvaluator instance.
     */
    public function electional(): \Vittix\Panchang\Electional\ElectionalEvaluator
    {
        return $this->panchang()->electional();
    }

    /**
     * Get the Hindu Calendar information (Samvat, Adhika Masa, etc) for a date.
     */
    public function calendar(DateTimeImmutable $date): \Vittix\Panchang\Calendar\HinduCalendarResult
    {
        return $this->panchang()->calendar($date);
    }

    /**
     * Calculate the Ascendant (Lagna) for a specific moment and location.
     */
    public function ascendant(DateTimeImmutable $moment, GeoLocation $location): \Vittix\Panchang\ValueObject\AscendantPosition
    {
        return $this->panchang()->ascendant($moment, $location);
    }

    /**
     * Calculate the full Kundali (Birth Chart) for a specific moment and location.
     */
    public function kundali(DateTimeImmutable $moment, GeoLocation $location): \Vittix\Panchang\ValueObject\Kundali
    {
        return $this->panchang()->kundali($moment, $location);
    }

    /**
     * Calculate the Vimshottari Dasha starting from a specific birth moment.
     *
     * @return array<\Vittix\Panchang\ValueObject\DashaPeriod>
     */
    public function vimshottariDasha(DateTimeImmutable $birthTime): array
    {
        return $this->panchang()->vimshottariDasha($birthTime);
    }

    /**
     * Calculate a specific Divisional Chart (Varga) for a given moment and location.
     */
    public function varga(DateTimeImmutable $moment, GeoLocation $location, \Vittix\Panchang\Enum\Varga $varga): \Vittix\Panchang\ValueObject\Kundali
    {
        return $this->panchang()->varga($moment, $location, $varga);
    }

    /**
     * Calculate the Shadbala (Six-fold planetary strength) for the visible planets.
     */
    public function shadbala(DateTimeImmutable $moment, GeoLocation $location): \Vittix\Panchang\ValueObject\Shadbala\Shadbala
    {
        return $this->panchang()->shadbala($moment, $location);
    }

    /**
     * Detect all classical planetary Yogas present in the birth chart.
     *
     * @return array<int, \Vittix\Panchang\ValueObject\Yoga>
     */
    public function yogas(DateTimeImmutable $moment, GeoLocation $location): array
    {
        return $this->panchang()->yogas($moment, $location);
    }
}
