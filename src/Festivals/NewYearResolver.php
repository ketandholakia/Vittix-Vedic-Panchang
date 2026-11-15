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
use Vittix\Panchang\Time\AstroTimeFactory;
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
    private const MAX_LUNATION_WALK = 16;

    private ?FestivalEngine $engine = null;

    private readonly AstroTimeFactory $timeFactory;

    /**
     * Memoised resolved new-year days: "month|year|tz|lat|lon" → day.
     *
     * @var array<string, DateTimeImmutable|null>
     */
    private array $dayCache = [];

    /** Memoised new-year years for repeated Samvat queries (bounded). */
    private readonly \Vittix\Panchang\Support\LruCache $yearCache;

    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
        private readonly TithiCalculator $tithiCalculator,
        private readonly LunarMonthCalculator $lunarMonth,
    ) {
        $this->timeFactory = new AstroTimeFactory();
        $this->yearCache = new \Vittix\Panchang\Support\LruCache(64);
    }

    /**
     * Resolve the civil day (00:00 local) on which the lunar new year named
     * $month begins in the given Gregorian year; null when the rule never
     * matches that year.
     *
     * Reference implementation: whole-year festival scan. The Samvat paths
     * use the equivalent (and much faster) {@see newYearDay()}.
     */
    public function resolve(LunarMonth $month, int $year, GeoLocation $location, DateTimeZone $timezone): ?DateTimeImmutable
    {
        return $this->engine()->resolve($this->festivalFor($month), $year, $location, $timezone);
    }

    /**
     * The civil day (00:00 local) on which the lunar new year named $month
     * begins in the given Gregorian year; null when the rule never matches.
     *
     * Walks back from an in-year anchor to the lunation opening the new
     * year, then scores only the 4 civil days from that new moon's local
     * date — the same rule the whole-year scan applies, without scanning
     * 365 days.
     */
    public function newYearDay(LunarMonth $month, int $year, GeoLocation $location, DateTimeZone $timezone): ?DateTimeImmutable
    {
        $cacheKey = sprintf(
            '%d|%d|%s|%.4f|%.4f',
            $month->value,
            $year,
            $timezone->getName(),
            $location->latitude,
            $location->longitude
        );
        if (array_key_exists($cacheKey, $this->dayCache)) {
            return $this->dayCache[$cacheKey];
        }

        // Chaitra falls in Mar/Apr, Kartika in Oct/Nov: anchor late enough
        // in the year that the most recent matching lunation is the one
        // naming this year.
        $anchor = $month === LunarMonth::Chaitra
            ? sprintf('%d-07-01 12:00', $year)
            : sprintf('%d-12-31 12:00', $year);

        $day = $this->mostRecentNewYearDay(
            $month,
            $this->timeFactory->fromDateTime(new DateTimeImmutable($anchor, $timezone)),
            $location,
            $timezone
        );

        $this->dayCache[$cacheKey] = $day;

        return $day;
    }

    /**
     * Gregorian year of the most recent new year for $month at or before
     * $instant. The year changes at 00:00 local on the resolved civil day.
     */
    public function newYearGregorianYear(
        LunarMonth $month,
        AstroInstant $instant,
        GeoLocation $location,
        DateTimeZone $timezone
    ): int {
        // Vikram and Shaka both anchor on Chaitra with identical arguments:
        // memoize so the second call is free.
        $cacheKey = sprintf(
            '%d|%.6F|%s|%.4f|%.4f',
            $month->value,
            $instant->julianDayUt,
            $timezone->getName(),
            $location->latitude,
            $location->longitude
        );
        $cached = $this->yearCache->get($cacheKey);
        if (is_int($cached)) {
            return $cached;
        }

        $day = $this->mostRecentNewYearDay($month, $instant, $location, $timezone);
        if ($day === null) {
            throw new RuntimeException(
                sprintf('Could not resolve a %s new-year day at or before the given instant.', $month->name)
            );
        }

        $year = (int) $day->format('Y');
        $this->yearCache->set($cacheKey, $year);

        return $year;
    }

    /**
     * The most recent new-year civil day for $month at or before $instant.
     *
     * The lunation named $month (non-Adhika) is the one containing the
     * Sun's sankranti into sign ($month - 1), whose opening new moon
     * precedes that sankranti. The anchor below finds that sankranti
     * directly (a cheap sun-only search, allowed to look up to 32 days
     * past the instant because the new-year DAY can precede the lunation's
     * sankranti by up to ~30 days), then the 16-lunation walk (as the
     * pre-2.7 code did) verifies the classification and scores only the
     * 4 civil days from the new moon's local date.
     */
    private function mostRecentNewYearDay(
        LunarMonth $month,
        AstroInstant $instant,
        GeoLocation $location,
        DateTimeZone $timezone
    ): ?DateTimeImmutable {
        $festival = $this->festivalFor($month);
        $targetSign = $month->value - 1; // Chaitra(1) → Mesha(0), Kartika(8) → Tula(7)

        $cursor = $instant;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $anchor = $this->timeFactory->fromDateTime(
                $cursor->utc->setTimezone($timezone)->modify('+32 days')
            );
            $sankranti = $this->previousSignEntry($anchor, $targetSign);
            // The naming lunation CONTAINS this sankranti: its opening new
            // moon is the one at-or-before the sankranti instant. Querying
            // sankranti-minus-a-day would overshoot when the sankranti
            // falls only hours after that new moon (e.g. Chaitra 2029: 6 h;
            // Kartika 2020: 14 h).
            $newMoon = $this->lunarMonth->previousNewMoon($sankranti);

            $lastWalkedNewMoon = $newMoon;

            for ($i = 0; $i < self::MAX_LUNATION_WALK; $i++) {
                $lunation = $this->lunarMonth->amantaLunation($newMoon);
                $lastWalkedNewMoon = $newMoon;

                if ($lunation->month === $month && !$lunation->isAdhika) {
                    // Shukla Pratipada starts at this new moon; the festival
                    // day is within the 4 civil days from its local date.
                    $windowStart = $newMoon->utc->setTimezone($timezone)->setTime(0, 0);
                    $day = $this->engine()->resolveNear($festival, $windowStart, 4, $location, $timezone, $lunation);

                    if ($day !== null && $day <= $instant->utc) {
                        return $day;
                    }
                    // The year has not flipped yet at $instant: re-anchor a
                    // lunation cycle earlier.
                    break;
                }

                $newMoon = $this->lunarMonth->previousNewMoon(
                    $this->timeFactory->fromDateTime($newMoon->utc->modify('-1 day'))
                );
            }

            // Re-anchor before the last walked lunation and retry (kshaya
            // skip, Adhika naming, or the not-yet-flipped case above).
            $cursor = $this->timeFactory->fromDateTime(
                $lastWalkedNewMoon->utc->modify('-45 days')
            );
        }

        return null;
    }

    /**
     * The most recent instant at or before $from at which the SIDEREAL Sun
     * enters $sign (a sankranti). Sun-only search: the sidereal solar rate
     * is ~0.9856°/day, so the entry lies within ~400 days and is located by
     * an unwrapped backward walk plus bisection on the sign boundary.
     */
    private function previousSignEntry(AstroInstant $from, int $sign): AstroInstant
    {
        $siderealSun = function (float $jd): float {
            return \Vittix\Panchang\Math\Angle::normalize(
                $this->astronomy->siderealLongitude(
                    \Vittix\Panchang\Enum\Body::Sun,
                    $this->timeFactory->fromJulianDay($jd)
                )
            );
        };

        $boundary = 30.0 * $sign;

        // Unwrap relative to $from so the entry crossing is a plain
        // downward crossing of $boundary when walking backward.
        $sidFrom = $siderealSun($from->julianDayUt);
        $wraps = (int) ceil(($boundary - $sidFrom) / 360.0);
        $unwrappedFrom = $sidFrom + 360.0 * $wraps; // >= $boundary

        $jdHigh = $from->julianDayUt;
        $unwHigh = $unwrappedFrom;
        $jdLow = $jdHigh;
        $unwLow = $unwHigh;

        // Backward walk (~1°/day): until the unwrapped longitude drops to
        // or below the sign boundary.
        for ($i = 0; $i < 500 && $unwLow > $boundary; $i++) {
            $jdPrev = $jdLow - 1.0;
            $unwPrev = $unwLow - ($unwLow - $this->unwrapAround(
                $siderealSun($jdPrev),
                $unwLow
            ));
            $jdLow = $jdPrev;
            $unwLow = $unwPrev;
        }

        if ($unwLow > $boundary) {
            // Should not happen within 500 days; fall back to the from instant.
            return $from;
        }

        // Bisect between $jdLow (<= boundary) and $jdHigh (> boundary).
        for ($i = 0; $i < 40; $i++) {
            $jdMid = ($jdLow + $jdHigh) / 2.0;
            $unwMid = $unwLow - ($unwLow - $this->unwrapAround(
                $siderealSun($jdMid),
                $unwLow + ($jdHigh - $jdLow) * 0.9856
            ));
            if ($unwMid <= $boundary) {
                $jdLow = $jdMid;
                $unwLow = $unwMid;
            } else {
                $jdHigh = $jdMid;
                $unwHigh = $unwMid;
            }
        }

        return $this->timeFactory->fromJulianDay($jdHigh);
    }

    /**
     * Lift a wrapped longitude into the same 360° cycle as $near so that
     * backward deltas stay small and positive.
     */
    private function unwrapAround(float $longitude, float $near): float
    {
        $delta = fmod($near - $longitude, 360.0);
        if ($delta > 180.0) {
            $delta -= 360.0;
        }
        if ($delta < -180.0) {
            $delta += 360.0;
        }

        return $near - $delta;
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
