<?php

declare(strict_types=1);

namespace Vittix\Panchang\Festivals;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Festivals\Rules\SunriseTithiRule;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Resolves the civil day on which a lunar new year begins, using the SAME
 * rule the festival engine applies to Shukla Pratipada (including the
 * greater-overlap fallback when the tithi never prevails at a sunrise).
 *
 * Consumers:
 *   • the festival engine (Gudi Padwa / Ugadi = Chaitra Shukla Pratipada);
 *   • {@see \Vittix\Panchang\Calendar\SamvatCalculator} — Vikram/Shaka
 *     change at Chaitra Shukla Pratipada, the Gujarati Vikram year at
 *     Kartika Shukla Pratipada. Both must agree with the festivals on the
 *     civil day, and the year changes at 00:00 local on that day.
 *
 * The underlying year-scan is cached per (year, timezone, location) in the
 * wrapped {@see FestivalEngine}, so repeated Samvat queries in one year
 * cost a single scan.
 */
final class NewYearResolver
{
    private ?FestivalEngine $engine = null;

    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
        private readonly TithiCalculator $tithiCalculator,
        private readonly LunarMonthCalculator $lunarMonth,
    ) {
    }

    /**
     * Resolve the civil day (00:00 local) on which the lunar new year named
     * $month begins in the given Gregorian year; null when the rule never
     * matches that year.
     */
    public function resolve(LunarMonth $month, int $year, GeoLocation $location, DateTimeZone $timezone): ?DateTimeImmutable
    {
        return $this->engine()->resolve($this->festivalFor($month), $year, $location, $timezone);
    }

    /**
     * Gregorian year of the most recent new year for $month at or before
     * $instant. The year changes at 00:00 local on the resolved civil day.
     *
     * New years fall in Mar/Apr (Chaitra) or Oct/Nov (Kartika), so at most
     * the query year and the year before need to be resolved — both hit the
     * engine's per-year cache.
     */
    public function newYearGregorianYear(
        LunarMonth $month,
        AstroInstant $instant,
        GeoLocation $location,
        DateTimeZone $timezone
    ): int {
        $queryYear = (int) $instant->utc->setTimezone($timezone)->format('Y');
        $festival = $this->festivalFor($month);

        $latest = null;
        foreach ([$queryYear - 1, $queryYear] as $year) {
            $day = $this->engine()->resolve($festival, $year, $location, $timezone);
            if ($day !== null && $day <= $instant->utc && ($latest === null || $day > $latest)) {
                $latest = $day;
            }
        }

        if ($latest === null) {
            throw new RuntimeException(
                sprintf('Could not resolve a %s new-year day at or before the given instant.', $month->name)
            );
        }

        return (int) $latest->format('Y');
    }

    private function festivalFor(LunarMonth $month): Festival
    {
        return new Festival(
            'lunar-new-year-' . $month->value,
            new SunriseTithiRule($month, Tithi::ShuklaPratipada)
        );
    }

    /**
     * One engine holding both new-year festivals: a single year-scan
     * resolves Chaitra and Kartika together, and the per-year cache is
     * shared across all Samvat queries.
     */
    private function engine(): FestivalEngine
    {
        if ($this->engine === null) {
            $engine = new FestivalEngine($this->astronomy, $this->tithiCalculator, $this->lunarMonth);
            // Registering both up front keeps the cached year-scan shared;
            // resolve() only reads the requested festival's entry.
            $engine->addFestival($this->festivalFor(LunarMonth::Chaitra));
            $engine->addFestival($this->festivalFor(LunarMonth::Kartika));
            $this->engine = $engine;
        }

        return $this->engine;
    }
}
