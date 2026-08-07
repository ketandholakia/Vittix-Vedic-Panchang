<?php

declare(strict_types=1);

namespace Vittix\Panchang\Panchanga;

use DateTimeImmutable;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Calendar\HinduCalendarCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\SolarMonth;
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
    private readonly AstroTimeFactory $timeFactory;

    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
        private readonly PanchangaCalculator $panchangaCalculator,
        private readonly HinduCalendarCalculator $calendarCalculator,
    ) {
        $this->timeFactory = new AstroTimeFactory();
    }

    /**
     * Create a PanchangService with default configuration.
     *
     * This is a convenience factory that creates all required dependencies
     * using the standard pure-PHP Lahiri engine.
     */
    public static function createDefault(): self
    {
        $panchang = \Vittix\Panchang\Panchang::createDefault();

        // Extract internal dependencies from the Panchang facade.
        // We re-create using the same pattern as Panchang::create().
        $engine = new \Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine();

        $lunarMonthCalc = new \Vittix\Panchang\Calendar\LunarMonthCalculator($engine);
        $adhikaMasaCalc = new \Vittix\Panchang\Calendar\AdhikaMasaCalculator($engine, $lunarMonthCalc);

        $calculator = new PanchangaCalculator(
            astronomy:      $engine,
            tithi:          new TithiCalculator($engine),
            nakshatra:      new NakshatraCalculator($engine),
            yoga:           new YogaCalculator($engine),
            karana:         new KaranaCalculator($engine),
            vara:           new VaraCalculator(),
            transitions:    new TransitionFinder($engine),
            lunarMonthCalc: $lunarMonthCalc,
            adhikaMasaCalc: $adhikaMasaCalc,
        );

        $calendar = new HinduCalendarCalculator(
            $lunarMonthCalc,
            $adhikaMasaCalc,
            new \Vittix\Panchang\Calendar\SamvatCalculator()
        );

        return new self($engine, $calculator, $calendar);
    }

    /**
     * Calculate the complete Panchang for a specific date and location.
     *
     * This is the main public method. Returns a PanchangResult that can
     * be serialized to JSON via `$result->toJson()` or inspected directly.
     */
    public function getForDate(
        DateTimeImmutable $date,
        GeoLocation $location
    ): PanchangResult {
        $timezone = $date->getTimezone();

        // Calculate the full PanchangDay (sunrise-anchored)
        $panchangDay = $this->panchangaCalculator->forDay($date, $location);

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

        return new PanchangResult(
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
            solarMonth: $solarMonth,
            vikramSamvat: $calendarResult->vikramSamvat,
            shakaSamvat: $calendarResult->shakaSamvat,
            ayanamsha: $this->astronomy->ayanamsa($instant),
            accuracy: $panchangDay->accuracy,
        );
    }

    /**
     * Get a pure astronomy report (no Panchang logic) for a date and location.
     *
     * This is the "AstronomyResult" output — solar events, lunar events,
     * ayanamsha, sidereal time, and longitudes without any calendar or
     * Panchang overlay.
     */
    public function getAstronomyReport(
        DateTimeImmutable $date,
        GeoLocation $location
    ): AstronomyResult {
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

        return new AstronomyResult(
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
    }
}
