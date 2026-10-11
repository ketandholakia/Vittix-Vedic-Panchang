<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Accuracy;

use DateTimeImmutable;
use RuntimeException;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Panchanga\TransitionFinder;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Compares engine output against the Swiss Ephemeris reference fixtures.
 *
 * All methods return flat, PHPStan-friendly record lists so that both the
 * accuracy tests and tools/accuracy_report.php can aggregate them.
 *
 * Sign conventions:
 *   • longitude errors are signed arcseconds: engine − reference, folded
 *     through the shortest angular difference (no 0/360 wrap artifacts).
 *   • time errors are signed seconds: engine − reference.
 *
 * @phpstan-type LongitudeRecord array{body: string, utc: string, error: float}
 * @phpstan-type TimeRecord array{utc: string, error: float}
 * @phpstan-type TransitionRecord array{limb: string, start_utc: string, error: float|null}
 * @phpstan-type TransitionDiagnosis array{limb: string, start_utc: string, reference_unix: float, engine_boundary_unix: float, returned_unix: float, pointing_error: float, solver_residual: float, total_error: float}
 * @phpstan-type EventRecord array{event: string, city: string, date: string, convention: string, engine_null: bool, slot0_null: bool, selection_mismatch: bool, matched_offset: int|null, error: float|null}
 * @phpstan-type Summary array{n: int, mean: float, rms: float, median: float, p95: float, max: float}
 */
final class AccuracyComparison
{
    /** Seconds between JD epoch and Unix epoch. */
    private const UNIX_EPOCH_JD = 2440587.5;

    /** Fixture body key => engine Body enum. */
    private const BODY_MAP = [
        'sun' => Body::Sun,
        'moon' => Body::Moon,
        'mercury' => Body::Mercury,
        'venus' => Body::Venus,
        'mars' => Body::Mars,
        'jupiter' => Body::Jupiter,
        'saturn' => Body::Saturn,
        'rahu_mean' => Body::RahuMean,
    ];

    /** Event types in the fixture. */
    public const EVENT_TYPES = ['sunrise', 'sunset', 'moonrise', 'moonset'];

    /** Rise/set conventions stored in the fixture. */
    public const CONVENTIONS = ['upper_limb_refraction', 'disc_center_no_refraction'];

    /** Transition limbs and their engine entry points. */
    public const LIMBS = ['tithi', 'nakshatra', 'yoga', 'karana'];

    /**
     * Rise/set events of the same type repeat roughly once per day, so a
     * candidate within this window is the same physical event. Engine times
     * further from every candidate are counted as selection mismatches
     * (the engine picked a different local day's event) instead of being
     * folded into the time-error statistics.
     */
    public const EVENT_MATCH_WINDOW_SECONDS = 7200.0;

    // ---------------------------------------------------------------------
    // Longitudes
    // ---------------------------------------------------------------------

    /**
     * @return array{
     *     samples: int,
     *     tropical: list<LongitudeRecord>,
     *     sidereal: list<LongitudeRecord>,
     *     ayanamsha_mean: list<TimeRecord>,
     *     ayanamsha_true: list<TimeRecord>
     * }
     */
    public static function longitudes(FixtureRepository $repo, AstronomyEngineInterface $engine): array
    {
        $factory = new AstroTimeFactory();

        $tropical = [];
        $sidereal = [];
        $ayanamshaMean = [];
        $ayanamshaTrue = [];

        $samples = $repo->samples('body_longitudes');
        foreach ($samples as $index => $sample) {
            $utc = FixtureRepository::stringField($sample, 'utc');
            $instant = $factory->fromDateTime(new DateTimeImmutable($utc));
            $bodies = FixtureRepository::arrayField($sample, 'bodies');

            foreach (self::BODY_MAP as $key => $body) {
                $reference = FixtureRepository::arrayField($bodies, $key);

                $tropicalError = self::arcsecDiff(
                    $engine->tropicalLongitude($body, $instant),
                    FixtureRepository::floatField($reference, 'tropical'),
                );
                $tropical[] = ['body' => $key, 'utc' => $utc, 'error' => $tropicalError];

                $siderealError = self::arcsecDiff(
                    $engine->siderealLongitude($body, $instant),
                    FixtureRepository::floatField($reference, 'sidereal'),
                );
                $sidereal[] = ['body' => $key, 'utc' => $utc, 'error' => $siderealError];
            }

            $engineAyanamsha = $engine->ayanamsa($instant);
            $ayanamshaMean[] = [
                'utc' => $utc,
                'error' => self::arcsecDiff($engineAyanamsha, FixtureRepository::floatField($sample, 'ayanamsha_mean')),
            ];
            $ayanamshaTrue[] = [
                'utc' => $utc,
                'error' => self::arcsecDiff($engineAyanamsha, FixtureRepository::floatField($sample, 'ayanamsha_true')),
            ];

            if ($index % 200 === 199 && method_exists($engine, 'clearCache')) {
                $engine->clearCache();
            }
        }

        return [
            'samples' => count($samples),
            'tropical' => $tropical,
            'sidereal' => $sidereal,
            'ayanamsha_mean' => $ayanamshaMean,
            'ayanamsha_true' => $ayanamshaTrue,
        ];
    }

    // ---------------------------------------------------------------------
    // Transitions
    // ---------------------------------------------------------------------

    /**
     * @return array{samples: int, records: list<TransitionRecord>}
     */
    public static function transitions(FixtureRepository $repo, AstronomyEngineInterface $engine): array
    {
        $factory = new AstroTimeFactory();
        $finder = new TransitionFinder($engine);
        $records = [];
        $samples = $repo->samples('transitions');

        foreach ($samples as $sample) {
            $startUtc = FixtureRepository::stringField($sample, 'start_utc');
            $from = $factory->fromDateTime(new DateTimeImmutable($startUtc));
            $until = $factory->fromDateTime((new DateTimeImmutable($startUtc))->modify('+5 days'));
            $limbs = FixtureRepository::arrayField($sample, 'limbs');

            foreach (self::LIMBS as $limb) {
                $reference = FixtureRepository::arrayField($limbs, $limb);
                $referenceUnix = self::unixFromJd(FixtureRepository::floatField($reference, 'end_jd_ut'));

                $end = match ($limb) {
                    'tithi' => $finder->findTithiEnd($from, $until),
                    'nakshatra' => $finder->findNakshatraEnd($from, $until),
                    'yoga' => $finder->findYogaEnd($from, $until),
                    'karana' => $finder->findKaranaEnd($from, $until),
                };

                $records[] = [
                    'limb' => $limb,
                    'start_utc' => $startUtc,
                    'error' => $end === null
                        ? null
                        : self::unixFromJd($end->julianDayUt) - $referenceUnix,
                ];
            }
        }

        return ['samples' => count($samples), 'records' => $records];
    }

    // ---------------------------------------------------------------------
    // Sun / Moon events
    // ---------------------------------------------------------------------

    /**
     * @return array{samples: int, records: list<EventRecord>}
     */
    public static function events(FixtureRepository $repo, AstronomyEngineInterface $engine): array
    {
        $factory = new AstroTimeFactory();
        $records = [];
        $samples = $repo->samples('sun_moon_events');

        foreach ($samples as $sample) {
            $city = FixtureRepository::stringField($sample, 'city');
            $date = FixtureRepository::stringField($sample, 'date');
            $timezone = FixtureRepository::stringField($sample, 'timezone');

            $location = new GeoLocation(
                latitude: FixtureRepository::floatField($sample, 'latitude'),
                longitude: FixtureRepository::floatField($sample, 'longitude'),
                elevationMeters: FixtureRepository::floatField($sample, 'elevation_m'),
            );

            $tz = new \DateTimeZone($timezone);
            $localDate = new DateTimeImmutable($date, $tz);

            $solar = $engine->solarEvents($localDate, $location, $tz);
            $lunar = $engine->lunarEvents($localDate, $location, $tz);

            $engineTimes = [
                'sunrise' => $solar->sunrise,
                'sunset' => $solar->sunset,
                'moonrise' => $lunar->moonrise,
                'moonset' => $lunar->moonset,
            ];

            $eventsFixture = FixtureRepository::arrayField($sample, 'events');

            foreach (self::EVENT_TYPES as $eventType) {
                $window = FixtureRepository::arrayField($eventsFixture, $eventType);
                $engineTime = $engineTimes[$eventType];
                // format('U') is timezone-independent, so this is the UTC instant.
                $engineUnix = $engineTime === null ? null : self::unixFromDateTime($engineTime);

                // Collect candidates per convention: offset => unix seconds.
                $candidates = [];
                foreach (self::CONVENTIONS as $convention) {
                    $candidates[$convention] = [];
                }
                $slot0Null = [];
                foreach (self::CONVENTIONS as $convention) {
                    $slot0Null[$convention] = true;
                }

                foreach ($window as $slot) {
                    if (!is_array($slot)) {
                        continue;
                    }
                    /** @var array<string, mixed> $slot */
                    $offset = FixtureRepository::intField($slot, 'offset');
                    $conventions = FixtureRepository::arrayField($slot, 'conventions');

                    foreach (self::CONVENTIONS as $convention) {
                        $entry = $conventions[$convention] ?? null;
                        if (!is_array($entry)) {
                            continue;
                        }
                        /** @var array<string, mixed> $entry */
                        $unix = self::unixFromJd(FixtureRepository::floatField($entry, 'jd_ut'));
                        $candidates[$convention][$offset] = $unix;
                        if ($offset === 0) {
                            $slot0Null[$convention] = false;
                        }
                    }
                }

                foreach (self::CONVENTIONS as $convention) {
                    $bestOffset = null;
                    $bestError = null;

                    if ($engineUnix !== null) {
                        foreach ($candidates[$convention] as $offset => $unix) {
                            $error = $engineUnix - $unix;
                            if (abs($error) > self::EVENT_MATCH_WINDOW_SECONDS) {
                                continue;
                            }
                            if ($bestError === null || abs($error) < abs($bestError)) {
                                $bestError = $error;
                                $bestOffset = $offset;
                            }
                        }
                    }

                    $records[] = [
                        'event' => $eventType,
                        'city' => $city,
                        'date' => $date,
                        'convention' => $convention,
                        'engine_null' => $engineUnix === null,
                        'slot0_null' => $slot0Null[$convention],
                        'selection_mismatch' => $engineUnix !== null && $bestError === null,
                        'matched_offset' => $bestOffset,
                        'error' => $bestError,
                    ];
                }
            }
        }

        return ['samples' => count($samples), 'records' => $records];
    }

    // ---------------------------------------------------------------------
    // Transition diagnostics (decomposition)
    // ---------------------------------------------------------------------

    /**
     * Recomputes the engine's own limb boundary with a robust independent
     * bisection on the engine's longitudes, and decomposes every comparison
     * into:
     *
     *   pointing_error  = engine's own boundary − reference boundary
     *   solver_residual = engine's returned time − engine's own boundary
     *   total_error     = returned − reference (= pointing + residual)
     *
     * A large solver residual means TransitionFinder returned a poorly
     * refined time (secant stall) rather than a longitude problem.
     *
     * @return list<TransitionDiagnosis>
     */
    public static function diagnoseTransitions(FixtureRepository $repo, AstronomyEngineInterface $engine): array
    {
        $result = self::transitions($repo, $engine);
        $referenceByStart = [];
        foreach ($repo->samples('transitions') as $sample) {
            $referenceByStart[FixtureRepository::stringField($sample, 'start_utc')] = $sample;
        }

        $diagnoses = [];
        foreach ($result['records'] as $record) {
            $limb = $record['limb'];
            $startUtc = $record['start_utc'];
            $sample = $referenceByStart[$startUtc];
            $limbs = FixtureRepository::arrayField($sample, 'limbs');
            $referenceUnix = self::unixFromJd(
                FixtureRepository::floatField(FixtureRepository::arrayField($limbs, $limb), 'end_jd_ut')
            );
            $engineBoundary = self::engineTrueBoundary($engine, $limb, $startUtc);
            $returned = $record['error'] === null ? $referenceUnix : $referenceUnix + $record['error'];

            $diagnoses[] = [
                'limb' => $limb,
                'start_utc' => $startUtc,
                'reference_unix' => $referenceUnix,
                'engine_boundary_unix' => $engineBoundary,
                'returned_unix' => $returned,
                'pointing_error' => $engineBoundary - $referenceUnix,
                'solver_residual' => $returned - $engineBoundary,
                'total_error' => $returned - $referenceUnix,
            ];
        }

        return $diagnoses;
    }

    /**
     * The engine's own limb boundary after `startUtc`, found with a robust
     * bisection directly on the engine's longitudes (independent of
     * TransitionFinder).
     */
    public static function engineTrueBoundary(AstronomyEngineInterface $engine, string $limb, string $startUtc): float
    {
        $toUtc = static fn (float $unix): string => gmdate('Y-m-d\TH:i:s\Z', (int) round($unix));

        $startUnix = (float) (new DateTimeImmutable($startUtc))->format('U');
        $startIndex = self::engineLimbIndex($engine, $limb, $toUtc($startUnix));

        // Coarse scan in 20-minute steps (no limb is longer than ~1.2 days).
        $lo = $startUnix;
        $hi = null;
        for ($step = 1; $step <= 200; $step++) {
            $probe = $startUnix + $step * 1200.0;
            if (self::engineLimbIndex($engine, $limb, $toUtc($probe)) !== $startIndex) {
                $lo = $startUnix + ($step - 1) * 1200.0;
                $hi = $probe;
                break;
            }
        }

        if ($hi === null) {
            throw new RuntimeException("No '{$limb}' boundary found within 200 scan steps from {$startUtc}.");
        }

        for ($iteration = 0; $iteration < 40 && ($hi - $lo) >= 0.25; $iteration++) {
            $mid = ($lo + $hi) / 2.0;
            if (self::engineLimbIndex($engine, $limb, $toUtc($mid)) === $startIndex) {
                $lo = $mid;
            } else {
                $hi = $mid;
            }
        }

        return $hi;
    }

    /**
     * Engine limb index at a UTC timestamp (mirrors the definitions used by
     * the Panchang calculators).
     */
    public static function engineLimbIndex(AstronomyEngineInterface $engine, string $limb, string $utc): int
    {
        $instant = (new AstroTimeFactory())->fromDateTime(new DateTimeImmutable($utc));
        $sun = $engine->tropicalLongitude(Body::Sun, $instant);
        $moon = $engine->tropicalLongitude(Body::Moon, $instant);

        return match ($limb) {
            'tithi' => (int) floor(Angle::normalize($moon - $sun) / 12.0),
            'karana' => (int) floor(Angle::normalize($moon - $sun) / 6.0),
            'nakshatra' => (int) floor(Angle::normalize($engine->siderealLongitude(Body::Moon, $instant)) / (360.0 / 27.0)),
            'yoga' => (int) floor(Angle::normalize(
                $engine->siderealLongitude(Body::Sun, $instant) + $engine->siderealLongitude(Body::Moon, $instant)
            ) / (360.0 / 27.0)),
            default => throw new RuntimeException("Unknown limb '{$limb}'."),
        };
    }

    // ---------------------------------------------------------------------
    // Numeric helpers
    // ---------------------------------------------------------------------

    /**
     * Shortest signed angular difference (engine − reference) in arcseconds.
     */
    public static function arcsecDiff(float $engine, float $reference): float
    {
        return Angle::normalizeSigned($engine - $reference) * 3600.0;
    }

    /**
     * Convert a Julian Day (UT) to Unix seconds.
     */
    public static function unixFromJd(float $julianDayUt): float
    {
        return ($julianDayUt - self::UNIX_EPOCH_JD) * 86400.0;
    }

    /**
     * Convert a DateTimeImmutable to Unix seconds (fractional).
     */
    public static function unixFromDateTime(DateTimeImmutable $moment): float
    {
        return (float) $moment->format('U') + ((float) $moment->format('u')) / 1_000_000.0;
    }

    /**
     * Summary statistics of a list of signed error values, reported as
     * magnitudes: mean/rms/median/p95/max are all computed on |error|.
     *
     * @param list<float> $values signed errors
     * @return Summary
     */
    public static function summarize(array $values): array
    {
        $n = count($values);
        if ($n === 0) {
            return ['n' => 0, 'mean' => 0.0, 'rms' => 0.0, 'median' => 0.0, 'p95' => 0.0, 'max' => 0.0];
        }

        $sum = 0.0;
        $squares = 0.0;
        $max = 0.0;
        $absolutes = [];
        foreach ($values as $value) {
            $abs = abs($value);
            $absolutes[] = $abs;
            $sum += $abs;
            $squares += $abs * $abs;
            $max = max($max, $abs);
        }

        sort($absolutes);
        $median = $absolutes[intdiv($n, 2)];
        $p95Index = min($n - 1, (int) ceil(0.95 * $n) - 1);
        $p95 = $absolutes[$p95Index];

        return [
            'n' => $n,
            'mean' => $sum / $n,
            'rms' => sqrt($squares / $n),
            'median' => $median,
            'p95' => $p95,
            'max' => $max,
        ];
    }

    /**
     * Decade label ("1950s") for an ISO-8601 UTC timestamp or date string.
     */
    public static function decadeOf(string $isoTimestamp): string
    {
        $year = (int) substr($isoTimestamp, 0, 4);

        return (intdiv($year, 10) * 10) . 's';
    }
}
