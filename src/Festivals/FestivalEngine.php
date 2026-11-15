<?php

declare(strict_types=1);

namespace Vittix\Panchang\Festivals;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Support\LruCache;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Resolves festivals to civil dates.
 *
 * Each festival resolves to exactly one date per year: every rule scores
 * every day of the year (tithi overlap with its kala window, Adhika
 * months skipped) and the day with the greatest overlap wins; ties go to
 * the later day (the classical second-sunrise convention for tithis
 * spanning two sunrises). The per-day context (solar events, tithi
 * timeline, lunar month machinery) is computed once per date and shared
 * across all rules.
 */
final class FestivalEngine
{
    /** Bound for the per-year resolution cache (year-scans are ~0.5 s). */
    public const RESOLUTION_CACHE_CAPACITY = 64;

    /** @var Festival[] */
    private array $catalog = [];

    private readonly TithiTimeline $tithiTimeline;

    /** Bumped whenever the catalog changes, invalidating cached resolutions. */
    private int $catalogVersion = 0;

    /**
     * Cached per-year resolutions: "version|year|tz|lat|lon" → name → date.
     * Bounded LRU — batch consumers touching many locations must not grow
     * the cache without limit (capacity 64 ≈ 64 year-scans).
     */
    private readonly LruCache $resolutionCache;

    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
        private readonly TithiCalculator $tithiCalculator,
        private readonly LunarMonthCalculator $lunarMonth,
    ) {
        $this->tithiTimeline = new TithiTimeline($astronomy, $tithiCalculator);
        $this->resolutionCache = new LruCache(self::RESOLUTION_CACHE_CAPACITY);
    }

    public function addFestival(Festival $festival): void
    {
        $this->catalog[] = $festival;
        $this->catalogVersion++;
    }

    /**
     * Get all festivals observed on the given civil date.
     *
     * Each festival resolves to exactly one date of the (Gregorian) year;
     * this date is cached, so scanning many days costs one year-scan.
     *
     * @return string[] List of festival names.
     */
    public function getFestivalsForDate(DateTimeImmutable $date, GeoLocation $location): array
    {
        $year = (int) $date->format('Y');
        $resolutions = $this->resolveYear($year, $date->getTimezone(), $location);

        $dayKey = $date->format('Y-m-d');
        $observed = [];
        foreach ($this->catalog as $festival) {
            $resolved = $resolutions[$festival->name] ?? null;
            if ($resolved !== null && $resolved->format('Y-m-d') === $dayKey) {
                $observed[] = $festival->name;
            }
        }

        return $observed;
    }

    /**
     * Resolve one festival to its single date in the given Gregorian year
     * (null when the rule never matches that year).
     */
    public function resolve(Festival $festival, int $year, GeoLocation $location, ?DateTimeZone $timezone = null): ?DateTimeImmutable
    {
        $tz = $timezone ?? new DateTimeZone('UTC');
        $resolutions = $this->resolveYear($year, $tz, $location);

        return $resolutions[$festival->name] ?? null;
    }

    /**
     * Resolve every catalog festival for one year in one day-scan.
     *
     * @return array<string, DateTimeImmutable|null> festival name → date at 00:00 local
     */
    private function resolveYear(int $year, DateTimeZone $timezone, GeoLocation $location): array
    {
        $cacheKey = sprintf(
            '%d|%d|%s|%.4f|%.4f',
            $this->catalogVersion,
            $year,
            $timezone->getName(),
            $location->latitude,
            $location->longitude
        );

        $cached = $this->resolutionCache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        /** @var array<string, array{score: float, date: DateTimeImmutable}> $best */
        $best = [];

        $day = new DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year), $timezone);
        $yearEnd = new DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year + 1), $timezone);

        $eventsNext = $this->astronomy->solarEvents($day, $location, $timezone);

        while ($day < $yearEnd) {
            // Rolling solar events: "today" was prefetched as "next" on the
            // previous iteration.
            $sunrise = $eventsNext->sunrise;
            $sunset = $eventsNext->sunset;
            $eventsNext = $this->astronomy->solarEvents($day->modify('+1 day'), $location, $timezone);

            $context = new FestivalDayContext(
                $day,
                $timezone,
                $sunrise,
                $sunset,
                $eventsNext->sunrise,
                $this->tithiTimeline,
                $this->lunarMonth,
            );

            foreach ($this->catalog as $festival) {
                $score = $festival->rule->score($context);
                if ($score === 0.0) {
                    // 0.0 = not observed and not adjacent; negative scores
                    // are the razor-edge gap fallback and still compete, but
                    // any positive-overlap day outranks every gap day.
                    continue;
                }
                $current = $best[$festival->name] ?? null;
                // Strictly greater: on equal scores the LATER day wins (the
                // classical second-sunrise convention).
                if ($current === null || $score >= $current['score']) {
                    $best[$festival->name] = ['score' => $score, 'date' => $day];
                }
            }

            $day = $day->modify('+1 day');
        }

        $resolutions = [];
        foreach ($this->catalog as $festival) {
            $resolutions[$festival->name] = $best[$festival->name]['date'] ?? null;
        }

        $this->resolutionCache->set($cacheKey, $resolutions);

        return $resolutions;
    }

    /**
     * Resolve one festival to its single best civil day within a bounded
     * window of $days days starting at $from (00:00 local). Same rule and
     * tie-breaking as the whole-year scan (greatest score, later day wins
     * on ties); no per-year cache is consulted or written.
     */
    public function resolveNear(
        Festival $festival,
        DateTimeImmutable $from,
        int $days,
        GeoLocation $location,
        DateTimeZone $timezone,
        ?\Vittix\Panchang\Calendar\Lunation $preverifiedLunation = null
    ): ?DateTimeImmutable {
        $day = $from->setTime(0, 0)->setTimezone($timezone);
        $end = $day->modify(sprintf('+%d days', max(1, $days)));

        /** @var array{score: float, date: DateTimeImmutable}|null $best */
        $best = null;
        $eventsNext = $this->astronomy->solarEvents($day, $location, $timezone);

        while ($day < $end) {
            $sunrise = $eventsNext->sunrise;
            $sunset = $eventsNext->sunset;
            $eventsNext = $this->astronomy->solarEvents($day->modify('+1 day'), $location, $timezone);

            $context = new FestivalDayContext(
                $day,
                $timezone,
                $sunrise,
                $sunset,
                $eventsNext->sunrise,
                $this->tithiTimeline,
                $this->lunarMonth,
                $preverifiedLunation
            );

            $score = $festival->rule->score($context);
            if ($score !== 0.0 && ($best === null || $score >= $best['score'])) {
                $best = ['score' => $score, 'date' => $day];
            }

            $day = $day->modify('+1 day');
        }

        return $best['date'] ?? null;
    }

    /**
     * Number of entries currently held by the per-year resolution cache.
     *
     * @internal exposed for cache-bound tests
     */
    public function resolutionCacheCount(): int
    {
        return count($this->resolutionCache);
    }
}
