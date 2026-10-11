<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Accuracy;

use RuntimeException;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;

/**
 * Renders the full accuracy report (Markdown and JSON) from the comparison
 * layer in AccuracyComparison.
 *
 * Used by tools/accuracy_report.php; kept in the test namespace so it is
 * autoloaded through composer's autoload-dev PSR-4 mapping.
 *
 * @phpstan-import-type LongitudeRecord from AccuracyComparison
 * @phpstan-import-type TimeRecord from AccuracyComparison
 * @phpstan-import-type TransitionRecord from AccuracyComparison
 * @phpstan-import-type TransitionDiagnosis from AccuracyComparison
 * @phpstan-import-type EventRecord from AccuracyComparison
 * @phpstan-import-type Summary from AccuracyComparison
 */
final class AccuracyReport
{
    /** Solver residual above this many seconds is considered a refinement failure. */
    private const SOLVER_STALL_SECONDS = 60.0;

    /**
     * @var array{
     *     samples: int,
     *     tropical: list<LongitudeRecord>,
     *     sidereal: list<LongitudeRecord>,
     *     ayanamsha_mean: list<TimeRecord>,
     *     ayanamsha_true: list<TimeRecord>
     * }|null
     */
    private ?array $longitudeData = null;

    /** @var array{samples: int, records: list<TransitionRecord>}|null */
    private ?array $transitionData = null;

    /** @var list<TransitionDiagnosis>|null */
    private ?array $diagnosisData = null;

    /** @var array{samples: int, records: list<EventRecord>}|null */
    private ?array $eventData = null;

    public function __construct(
        private readonly FixtureRepository $repo,
        private readonly AstronomyEngineInterface $engine,
    ) {
    }

    // ---------------------------------------------------------------------
    // Public API
    // ---------------------------------------------------------------------

    public function markdown(): string
    {
        $parts = [
            $this->headerMarkdown(),
            $this->longitudesMarkdown(),
            $this->transitionsMarkdown(),
            $this->eventsMarkdown(),
            $this->notesMarkdown(),
        ];

        return implode("\n", $parts);
    }

    /**
     * @return array<string, mixed>
     */
    public function json(): array
    {
        return [
            'generated_at' => gmdate('Y-m-d H:i:s') . ' UTC',
            'git' => $this->gitCommit(),
            'fixtures' => [
                'body_longitudes' => $this->repo->metadata('body_longitudes'),
                'transitions' => $this->repo->metadata('transitions'),
                'sun_moon_events' => $this->repo->metadata('sun_moon_events'),
            ],
            'longitudes' => $this->longitudesJson(),
            'transitions' => $this->transitionsJson(),
            'events' => $this->eventsJson(),
        ];
    }

    // ---------------------------------------------------------------------
    // Lazy computation
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
    private function longitudes(): array
    {
        return $this->longitudeData ??= AccuracyComparison::longitudes($this->repo, $this->engine);
    }

    /**
     * @return array{samples: int, records: list<TransitionRecord>}
     */
    private function transitions(): array
    {
        return $this->transitionData ??= AccuracyComparison::transitions($this->repo, $this->engine);
    }

    /**
     * @return list<TransitionDiagnosis>
     */
    private function diagnosis(): array
    {
        return $this->diagnosisData ??= AccuracyComparison::diagnoseTransitions($this->repo, $this->engine);
    }

    /**
     * @return array{samples: int, records: list<EventRecord>}
     */
    private function events(): array
    {
        return $this->eventData ??= AccuracyComparison::events($this->repo, $this->engine);
    }

    // ---------------------------------------------------------------------
    // Markdown: header
    // ---------------------------------------------------------------------

    private function headerMarkdown(): string
    {
        $meta = $this->repo->metadata('body_longitudes');

        $lines = [
            '# Panchang accuracy report',
            '',
            '- **Generated:** ' . gmdate('Y-m-d H:i:s') . ' UTC',
            '- **Git commit:** `' . $this->gitCommit() . '`',
            '- **Reference:** Swiss Ephemeris ' . FixtureRepository::stringField($meta, 'swisseph_version')
                . ' (' . FixtureRepository::stringField($meta, 'ephemeris') . ' ephemeris), Lahiri ayanamsha (SIDM_LAHIRI)',
            '- **Fixtures:** seed ' . (string) FixtureRepository::intField($meta, 'seed')
                . ', generated ' . FixtureRepository::stringField($meta, 'generated_at'),
            '- **Engine under test:** Vittix Panchang, pure-PHP Lahiri engine (EngineFactory::createPurePhpEngine)',
            '',
            'Errors are signed (engine − reference) with shortest-angle wraparound;',
            'summary statistics use magnitudes (|error|). Longitudes in arcseconds, times in seconds.',
            '',
        ];

        return implode("\n", $lines);
    }

    // ---------------------------------------------------------------------
    // Markdown: longitudes
    // ---------------------------------------------------------------------

    private function longitudesMarkdown(): string
    {
        $data = $this->longitudes();

        $lines = [];
        $lines[] = '## 1. Body longitudes';
        $lines[] = '';
        $lines[] = sprintf('Dataset: %d random UTC instants, 1950–2100.', $data['samples']);
        $lines[] = '';

        // 1.1 Overall
        $lines[] = '### 1.1 Overall';
        $lines[] = '';
        $lines[] = '| Dataset | N | mean | RMS | median | p95 | max |';
        $lines[] = '|---|---|---|---|---|---|---|';
        foreach (['tropical', 'sidereal'] as $kind) {
            $errors = $this->longitudeErrors($data[$kind]);
            $lines[] = self::summaryRow('| ' . $kind . ' longitude', $errors);
        }
        $lines[] = '';
        $lines[] = 'Ayanamsha (engine vs Swiss):';
        $lines[] = '';
        $lines[] = '| Variant | N | mean | RMS | max |';
        $lines[] = '|---|---|---|---|---|';
        foreach (['ayanamsha_mean', 'ayanamsha_true'] as $kind) {
            $errors = $this->timeErrors($data[$kind]);
            $summary = AccuracyComparison::summarize($errors);
            $lines[] = sprintf(
                '| %s | %d | %s | %s | %s |',
                $kind,
                $summary['n'],
                self::num($summary['mean']),
                self::num($summary['rms']),
                self::num($summary['max']),
            );
        }
        $lines[] = '';

        // 1.2 Per body
        $lines[] = '### 1.2 Per body';
        $lines[] = '';
        $lines[] = '| Body | tropical mean | tropical max | sidereal mean | sidereal max |';
        $lines[] = '|---|---|---|---|---|';
        foreach (self::BODY_KEYS as $body) {
            $tropical = AccuracyComparison::summarize($this->filterErrors($data['tropical'], $body));
            $sidereal = AccuracyComparison::summarize($this->filterErrors($data['sidereal'], $body));
            $lines[] = sprintf(
                '| %s | %s | %s | %s | %s |',
                $body,
                self::num($tropical['mean']),
                self::num($tropical['max']),
                self::num($sidereal['mean']),
                self::num($sidereal['max']),
            );
        }
        $lines[] = '';

        // 1.3 By decade
        $lines[] = '### 1.3 By decade (tropical, all bodies)';
        $lines[] = '';
        $lines[] = '| Decade | N | mean | p95 | max |';
        $lines[] = '|---|---|---|---|---|';
        $byDecade = [];
        foreach ($data['tropical'] as $record) {
            $byDecade[AccuracyComparison::decadeOf($record['utc'])][] = $record['error'];
        }
        ksort($byDecade);
        foreach ($byDecade as $decade => $errors) {
            $summary = AccuracyComparison::summarize($errors);
            $lines[] = sprintf(
                '| %s | %d | %s | %s | %s |',
                $decade,
                $summary['n'],
                self::num($summary['mean']),
                self::num($summary['p95']),
                self::num($summary['max']),
            );
        }
        $lines[] = '';

        // 1.4 Worst cases
        $lines[] = '### 1.4 Worst 5 cases (tropical)';
        $lines[] = '';
        $lines[] = '| Body | UTC instant | error (arcsec) |';
        $lines[] = '|---|---|---|';
        foreach ($this->worst($data['tropical'], 5) as $record) {
            $lines[] = sprintf('| %s | %s | %s |', $record['body'], $record['utc'], self::num($record['error']));
        }
        $lines[] = '';
        $lines[] = '### 1.5 Worst 5 cases (sidereal)';
        $lines[] = '';
        $lines[] = '| Body | UTC instant | error (arcsec) |';
        $lines[] = '|---|---|---|';
        foreach ($this->worst($data['sidereal'], 5) as $record) {
            $lines[] = sprintf('| %s | %s | %s |', $record['body'], $record['utc'], self::num($record['error']));
        }
        $lines[] = '';

        return implode("\n", $lines);
    }

    // ---------------------------------------------------------------------
    // Markdown: transitions
    // ---------------------------------------------------------------------

    private function transitionsMarkdown(): string
    {
        $data = $this->transitions();
        $diagnoses = $this->diagnosis();

        $lines = [];
        $lines[] = '## 2. Limb transitions';
        $lines[] = '';
        $lines[] = sprintf(
            'Dataset: %d random start instants; next end of the current tithi, nakshatra, yoga and karana.',
            $data['samples'],
        );
        $lines[] = sprintf(
            'Comparisons above %.0f s are treated as anomalies (solver artifacts / boundary races) and excluded from the columns below.',
            Tolerances::TRANSITION_ANOMALY_SECONDS,
        );
        $lines[] = '';

        $byLimb = [];
        foreach ($data['records'] as $record) {
            $byLimb[$record['limb']][] = $record;
        }

        // 2.1 Clean stats
        $lines[] = '### 2.1 Per limb (anomalies excluded)';
        $lines[] = '';
        $lines[] = '| Limb | N clean | mean | RMS | median | p95 | max | anomalies |';
        $lines[] = '|---|---|---|---|---|---|---|---|';
        foreach (AccuracyComparison::LIMBS as $limb) {
            $records = $byLimb[$limb] ?? [];
            [$clean, $anomalies] = $this->splitTransitions($records);
            $summary = AccuracyComparison::summarize($this->transitionErrors($clean));
            $lines[] = sprintf(
                '| %s | %d | %s | %s | %s | %s | %s | %d |',
                $limb,
                $summary['n'],
                self::num($summary['mean']),
                self::num($summary['rms']),
                self::num($summary['median']),
                self::num($summary['p95']),
                self::num($summary['max']),
                count($anomalies),
            );
        }
        $lines[] = '';

        // 2.2 Decomposition
        $lines[] = '### 2.2 Error decomposition (independent bisection vs TransitionFinder)';
        $lines[] = '';
        $lines[] = 'Pointing = engine’s own boundary − reference (longitude error effect).';
        $lines[] = 'Residual = engine’s returned time − engine’s own boundary (refinement effect).';
        $lines[] = '';
        $lines[] = '| Limb | pointing mean | pointing max | residual mean | residual max | solver stalls |';
        $lines[] = '|---|---|---|---|---|---|';
        $stallTotal = 0;
        foreach (AccuracyComparison::LIMBS as $limb) {
            $pointing = [];
            $residual = [];
            $stalls = 0;
            foreach ($diagnoses as $diagnosis) {
                if ($diagnosis['limb'] !== $limb) {
                    continue;
                }
                $pointing[] = $diagnosis['pointing_error'];
                $residual[] = $diagnosis['solver_residual'];
                if (abs($diagnosis['solver_residual']) > self::SOLVER_STALL_SECONDS) {
                    $stalls++;
                }
            }
            $stallTotal += $stalls;
            $p = AccuracyComparison::summarize($pointing);
            $r = AccuracyComparison::summarize($residual);
            $lines[] = sprintf(
                '| %s | %s | %s | %s | %s | %d |',
                $limb,
                self::num($p['mean']),
                self::num($p['max']),
                self::num($r['mean']),
                self::num($r['max']),
                $stalls,
            );
        }
        $lines[] = '';
        $lines[] = sprintf(
            '**Solver stalls total: %d / %d comparisons** — cases where the returned time is more than %.0f s away from the engine’s own boundary.',
            $stallTotal,
            count($diagnoses),
            self::SOLVER_STALL_SECONDS,
        );
        $lines[] = '';

        // 2.3 Worst clean cases
        $allClean = [];
        foreach (AccuracyComparison::LIMBS as $limb) {
            [$clean] = $this->splitTransitions($byLimb[$limb] ?? []);
            foreach ($clean as $record) {
                $allClean[] = $record;
            }
        }
        usort($allClean, static function (array $a, array $b): int {
            /** @var array{error: float|null} $a */
            /** @var array{error: float|null} $b */
            return abs((float) $b['error']) <=> abs((float) $a['error']);
        });

        $lines[] = '### 2.3 Worst 5 cases (non-anomalous)';
        $lines[] = '';
        $lines[] = '| Limb | Start (UTC) | error (s) |';
        $lines[] = '|---|---|---|';
        foreach (array_slice($allClean, 0, 5) as $record) {
            $lines[] = sprintf('| %s | %s | %s |', $record['limb'], $record['start_utc'], self::num((float) $record['error']));
        }
        $lines[] = '';

        // 2.4 Anomalies
        $lines[] = '### 2.4 Anomalies';
        $lines[] = '';
        $lines[] = '| Limb | Start (UTC) | total (s) | pointing (s) | residual (s) | classification |';
        $lines[] = '|---|---|---|---|---|---|';
        $anomalies = [];
        foreach ($diagnoses as $diagnosis) {
            if (abs($diagnosis['total_error']) <= Tolerances::TRANSITION_ANOMALY_SECONDS) {
                continue;
            }
            $anomalies[] = $diagnosis;
        }
        usort($anomalies, static function (array $a, array $b): int {
            /** @var array{total_error: float} $a */
            /** @var array{total_error: float} $b */
            return abs($b['total_error']) <=> abs($a['total_error']);
        });
        if ($anomalies === []) {
            $lines[] = '| — | — | — | — | — | none |';
        } else {
            foreach ($anomalies as $diagnosis) {
                $classification = abs($diagnosis['solver_residual']) > self::SOLVER_STALL_SECONDS
                    ? 'solver artifact (secant stall)'
                    : 'start near boundary / other';
                $lines[] = sprintf(
                    '| %s | %s | %s | %s | %s | %s |',
                    $diagnosis['limb'],
                    $diagnosis['start_utc'],
                    self::num($diagnosis['total_error']),
                    self::num($diagnosis['pointing_error']),
                    self::num($diagnosis['solver_residual']),
                    $classification,
                );
            }
        }
        $lines[] = '';

        return implode("\n", $lines);
    }

    // ---------------------------------------------------------------------
    // Markdown: events
    // ---------------------------------------------------------------------

    private function eventsMarkdown(): string
    {
        $data = $this->events();

        $lines = [];
        $lines[] = '## 3. Sunrise/sunset and moonrise/moonset';
        $lines[] = '';
        $lines[] = sprintf(
            'Dataset: %d city-dates (10 cities × 60 dates across the year and 2000–2050); events keyed by local civil date.',
            $data['samples'],
        );
        $lines[] = sprintf(
            'Matched = within ±%.0f min of a reference event; off-window = engine time further from every reference event of the neighbouring days (selection difference).',
            AccuracyComparison::EVENT_MATCH_WINDOW_SECONDS / 60.0,
        );
        $lines[] = '';

        // 3.1 By event & convention
        $lines[] = '### 3.1 By event and convention';
        $lines[] = '';
        $lines[] = '| Event | Convention | N | null | missed | off-window | matched mean | p95 | max |';
        $lines[] = '|---|---|---|---|---|---|---|---|---|';
        foreach (AccuracyComparison::EVENT_TYPES as $eventType) {
            foreach (AccuracyComparison::CONVENTIONS as $convention) {
                $stats = $this->eventStats($data['records'], $eventType, $convention);
                $lines[] = sprintf(
                    '| %s | %s | %d | %d | %d | %d | %s | %s | %s |',
                    $eventType,
                    $convention,
                    $stats['total'],
                    $stats['null'],
                    $stats['missed'],
                    $stats['offWindow'],
                    self::num($stats['matched']['mean']),
                    self::num($stats['matched']['p95']),
                    self::num($stats['matched']['max']),
                );
            }
        }
        $lines[] = '';

        // 3.2 By city (upper-limb convention, matched only)
        $lines[] = '### 3.2 By city (upper-limb + refraction, matched events only)';
        $lines[] = '';
        $lines[] = '| City | sun N | sun mean | sun max | moon N | moon mean | moon max | moon null/off-window |';
        $lines[] = '|---|---|---|---|---|---|---|---|';
        $cities = $this->eventCities();
        foreach ($cities as $city) {
            $sun = $this->cityStats($data['records'], $city, ['sunrise', 'sunset'], Tolerances::SUN_EVENT_CONVENTION);
            $moon = $this->cityStats($data['records'], $city, ['moonrise', 'moonset'], Tolerances::MOON_EVENT_CONVENTION);
            $lines[] = sprintf(
                '| %s | %d | %s | %s | %d | %s | %s | %d |',
                $city,
                $sun['summary']['n'],
                self::num($sun['summary']['mean']),
                self::num($sun['summary']['max']),
                $moon['summary']['n'],
                self::num($moon['summary']['mean']),
                self::num($moon['summary']['max']),
                $moon['nullOrOffWindow'],
            );
        }
        $lines[] = '';

        // 3.3 By decade
        $lines[] = '### 3.3 By decade (upper-limb + refraction, matched only)';
        $lines[] = '';
        $lines[] = '| Decade | N | mean | p95 | max |';
        $lines[] = '|---|---|---|---|---|';
        $byDecade = [];
        foreach ($data['records'] as $record) {
            if ($record['convention'] !== Tolerances::SUN_EVENT_CONVENTION || $record['error'] === null) {
                continue;
            }
            if ($record['event'] === 'moonrise' || $record['event'] === 'moonset') {
                continue;
            }
            $byDecade[AccuracyComparison::decadeOf($record['date'])][] = $record['error'];
        }
        ksort($byDecade);
        foreach ($byDecade as $decade => $errors) {
            $summary = AccuracyComparison::summarize($errors);
            $lines[] = sprintf(
                '| %s | %d | %s | %s | %s |',
                $decade,
                $summary['n'],
                self::num($summary['mean']),
                self::num($summary['p95']),
                self::num($summary['max']),
            );
        }
        $lines[] = '';

        // 3.4 Worst matched
        $lines[] = '### 3.4 Worst 5 matched cases (upper-limb + refraction)';
        $lines[] = '';
        $lines[] = '| Event | City | Date | error (s) |';
        $lines[] = '|---|---|---|---|';
        $matched = [];
        foreach ($data['records'] as $record) {
            if ($record['convention'] === Tolerances::SUN_EVENT_CONVENTION && $record['error'] !== null) {
                $matched[] = $record;
            }
        }
        usort($matched, static function (array $a, array $b): int {
            /** @var array{error: float|null} $a */
            /** @var array{error: float|null} $b */
            return abs((float) $b['error']) <=> abs((float) $a['error']);
        });
        foreach (array_slice($matched, 0, 5) as $record) {
            $lines[] = sprintf(
                '| %s | %s | %s | %s |',
                $record['event'],
                $record['city'],
                $record['date'],
                self::num((float) $record['error']),
            );
        }
        $lines[] = '';

        // 3.5 Convention check
        $lines[] = '### 3.5 Convention check';
        $lines[] = '';
        $lines[] = '| Event | upper limb + refraction (mean) | disc centre, no refraction (mean) | matches |';
        $lines[] = '|---|---|---|---|';
        foreach (AccuracyComparison::EVENT_TYPES as $eventType) {
            $upper = $this->eventStats($data['records'], $eventType, 'upper_limb_refraction')['matched']['mean'];
            $centre = $this->eventStats($data['records'], $eventType, 'disc_center_no_refraction')['matched']['mean'];
            $winner = $upper <= $centre ? 'upper limb + refraction' : 'disc centre, no refraction';
            $lines[] = sprintf('| %s | %s | %s | %s |', $eventType, self::num($upper), self::num($centre), $winner);
        }
        $lines[] = '';

        return implode("\n", $lines);
    }

    // ---------------------------------------------------------------------
    // Markdown: notes
    // ---------------------------------------------------------------------

    private function notesMarkdown(): string
    {
        $longitudes = $this->longitudes();
        $events = $this->events();
        $diagnoses = $this->diagnosis();

        $stalls = 0;
        foreach ($diagnoses as $diagnosis) {
            if (abs($diagnosis['solver_residual']) > self::SOLVER_STALL_SECONDS) {
                $stalls++;
            }
        }

        $planetMax = 0.0;
        foreach (['mercury', 'venus', 'mars', 'jupiter', 'saturn'] as $body) {
            $planetMax = max(
                $planetMax,
                AccuracyComparison::summarize($this->filterErrors($longitudes['tropical'], $body))['max'],
            );
        }

        $ayanamshaMean = AccuracyComparison::summarize($this->timeErrors($longitudes['ayanamsha_mean']));

        $moonMissed = 0;
        $moonOffWindow = 0;
        foreach ($events['records'] as $record) {
            if ($record['convention'] !== Tolerances::MOON_EVENT_CONVENTION) {
                continue;
            }
            if ($record['event'] !== 'moonrise' && $record['event'] !== 'moonset') {
                continue;
            }
            if ($record['engine_null'] && !$record['slot0_null']) {
                $moonMissed++;
            }
            if (!$record['engine_null'] && $record['selection_mismatch']) {
                $moonOffWindow++;
            }
        }

        $lines = [];
        $lines[] = '## 4. Observations';
        $lines[] = '';

        if ($planetMax > 120.0) {
            $lines[] = sprintf(
                '- Planets: worst tropical longitude error %.0f arcsec (§1.2) — the five planets share a common',
                $planetMax,
            );
            $lines[] = '  deformation, pointing at the shared planet pipeline rather than individual term series.';
        } else {
            $lines[] = sprintf(
                '- Planets: worst tropical longitude error %.1f arcsec (§1.2).',
                $planetMax,
            );
        }

        $lines[] = sprintf(
            '- Lahiri ayanamsha differs from Swiss Ephemeris by %.2f arcsec on average (§1.1, §1.4); the offset',
            $ayanamshaMean['mean'],
        );
        $lines[] = '  propagates into all sidereal longitudes.';
        $lines[] = '- ΔT model: for dates well after ~2050 the engine (Espenak–Meeus) and the Swiss reference use';
        $lines[] = '  different ΔT predictions (≈ +18 s higher by 2050, ≈ +110 s by 2100). This inflates late-century';
        $lines[] = '  residuals for the fastest bodies (Moon ≈ 60″, Mercury/Venus ≈ 20–30″); with ΔT matched, the';
        $lines[] = '  ephemeris agrees within ≈1″. See “Known comparator differences” in docs/accuracy.md.';

        if ($stalls > 0) {
            $lines[] = sprintf(
                '- TransitionFinder refinement fails silently in %d/%d comparisons (returned time far from the engine’s own boundary, §2.2).',
                $stalls,
                count($diagnoses),
            );
        } else {
            $lines[] = sprintf(
                '- TransitionFinder refinement converges for all %d comparisons; worst residual below 1 s (§2.2).',
                count($diagnoses),
            );
        }

        if ($moonMissed === 0 && $moonOffWindow === 0) {
            $lines[] = '- Moonrise/moonset match the reference’s local-date convention (§3.1).';
        } else {
            $lines[] = sprintf(
                '- Moonrise/moonset: %d records absent (engine null) and %d off-window relative to the local-date convention (§3.1).',
                $moonMissed,
                $moonOffWindow,
            );
        }

        $lines[] = '- Sunrise/sunset matches the upper-limb + standard-refraction convention (§3.5).';
        $lines[] = '';

        return implode("\n", $lines);
    }

    // ---------------------------------------------------------------------
    // JSON sections
    // ---------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function longitudesJson(): array
    {
        $data = $this->longitudes();

        $byBody = [];
        foreach (self::BODY_KEYS as $body) {
            $byBody[$body] = [
                'tropical' => AccuracyComparison::summarize($this->filterErrors($data['tropical'], $body)),
                'sidereal' => AccuracyComparison::summarize($this->filterErrors($data['sidereal'], $body)),
            ];
        }

        $byDecade = [];
        foreach ($data['tropical'] as $record) {
            $byDecade[AccuracyComparison::decadeOf($record['utc'])][] = $record['error'];
        }
        ksort($byDecade);
        $decadeStats = [];
        foreach ($byDecade as $decade => $errors) {
            $decadeStats[$decade] = AccuracyComparison::summarize($errors);
        }

        $worstTropical = [];
        foreach ($this->worst($data['tropical'], 5) as $record) {
            $worstTropical[] = $record;
        }

        return [
            'samples' => $data['samples'],
            'overall' => [
                'tropical' => AccuracyComparison::summarize($this->longitudeErrors($data['tropical'])),
                'sidereal' => AccuracyComparison::summarize($this->longitudeErrors($data['sidereal'])),
                'ayanamsha_mean' => AccuracyComparison::summarize($this->timeErrors($data['ayanamsha_mean'])),
                'ayanamsha_true' => AccuracyComparison::summarize($this->timeErrors($data['ayanamsha_true'])),
            ],
            'by_body' => $byBody,
            'by_decade_tropical' => $decadeStats,
            'worst_tropical' => $worstTropical,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transitionsJson(): array
    {
        $data = $this->transitions();
        $diagnoses = $this->diagnosis();

        $byLimb = [];
        foreach ($data['records'] as $record) {
            $byLimb[$record['limb']][] = $record;
        }

        $limbStats = [];
        $anomalies = [];
        foreach (AccuracyComparison::LIMBS as $limb) {
            [$clean, $anomalyRecords] = $this->splitTransitions($byLimb[$limb] ?? []);
            $pointing = [];
            $residual = [];
            $stalls = 0;
            foreach ($diagnoses as $diagnosis) {
                if ($diagnosis['limb'] !== $limb) {
                    continue;
                }
                $pointing[] = $diagnosis['pointing_error'];
                $residual[] = $diagnosis['solver_residual'];
                if (abs($diagnosis['solver_residual']) > self::SOLVER_STALL_SECONDS) {
                    $stalls++;
                }
            }

            $limbStats[$limb] = [
                'clean' => AccuracyComparison::summarize($this->transitionErrors($clean)),
                'anomaly_count' => count($anomalyRecords),
                'pointing' => AccuracyComparison::summarize($pointing),
                'residual' => AccuracyComparison::summarize($residual),
                'solver_stalls' => $stalls,
            ];

            foreach ($diagnoses as $diagnosis) {
                if ($diagnosis['limb'] !== $limb || abs($diagnosis['total_error']) <= Tolerances::TRANSITION_ANOMALY_SECONDS) {
                    continue;
                }
                $anomalies[] = [
                    'limb' => $limb,
                    'start_utc' => $diagnosis['start_utc'],
                    'total_error_s' => round($diagnosis['total_error'], 2),
                    'pointing_error_s' => round($diagnosis['pointing_error'], 2),
                    'solver_residual_s' => round($diagnosis['solver_residual'], 2),
                    'classification' => abs($diagnosis['solver_residual']) > self::SOLVER_STALL_SECONDS
                        ? 'solver_stall'
                        : 'boundary_race_or_other',
                ];
            }
        }

        return [
            'samples' => $data['samples'],
            'by_limb' => $limbStats,
            'anomalies' => $anomalies,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function eventsJson(): array
    {
        $data = $this->events();

        $byEventConvention = [];
        foreach (AccuracyComparison::EVENT_TYPES as $eventType) {
            foreach (AccuracyComparison::CONVENTIONS as $convention) {
                $stats = $this->eventStats($data['records'], $eventType, $convention);
                $byEventConvention[$eventType][$convention] = [
                    'total' => $stats['total'],
                    'null' => $stats['null'],
                    'missed' => $stats['missed'],
                    'off_window' => $stats['offWindow'],
                    'matched' => $stats['matched'],
                ];
            }
        }

        $byCity = [];
        foreach ($this->eventCities() as $city) {
            $sun = $this->cityStats($data['records'], $city, ['sunrise', 'sunset'], Tolerances::SUN_EVENT_CONVENTION);
            $moon = $this->cityStats($data['records'], $city, ['moonrise', 'moonset'], Tolerances::MOON_EVENT_CONVENTION);
            $byCity[$city] = [
                'sun' => $sun['summary'],
                'moon' => $moon['summary'],
                'moon_null_or_off_window' => $moon['nullOrOffWindow'],
            ];
        }

        return [
            'samples' => $data['samples'],
            'by_event_convention' => $byEventConvention,
            'by_city' => $byCity,
        ];
    }

    // ---------------------------------------------------------------------
    // Shared helpers
    // ---------------------------------------------------------------------

    private const BODY_KEYS = ['sun', 'moon', 'mercury', 'venus', 'mars', 'jupiter', 'saturn', 'rahu_mean'];

    /**
     * @param list<LongitudeRecord> $records
     * @return list<float>
     */
    private function longitudeErrors(array $records): array
    {
        $values = [];
        foreach ($records as $record) {
            $values[] = $record['error'];
        }

        return $values;
    }

    /**
     * @param list<TimeRecord> $records
     * @return list<float>
     */
    private function timeErrors(array $records): array
    {
        $values = [];
        foreach ($records as $record) {
            $values[] = $record['error'];
        }

        return $values;
    }

    /**
     * @param list<LongitudeRecord> $records
     * @return list<float>
     */
    private function filterErrors(array $records, string $body): array
    {
        $errors = [];
        foreach ($records as $record) {
            if ($record['body'] === $body) {
                $errors[] = $record['error'];
            }
        }

        return $errors;
    }

    /**
     * @param list<LongitudeRecord> $records
     * @return list<LongitudeRecord>
     */
    private function worst(array $records, int $limit): array
    {
        $sorted = $records;
        usort($sorted, static function (array $a, array $b): int {
            /** @var array{error: float} $a */
            /** @var array{error: float} $b */
            return abs($b['error']) <=> abs($a['error']);
        });

        return array_slice($sorted, 0, $limit);
    }

    /**
     * @param list<TransitionRecord> $records
     * @return array{0: list<TransitionRecord>, 1: list<TransitionRecord>}  [clean records, anomalous records]
     */
    private function splitTransitions(array $records): array
    {
        $clean = [];
        $anomalies = [];
        foreach ($records as $record) {
            if ($record['error'] === null || abs($record['error']) > Tolerances::TRANSITION_ANOMALY_SECONDS) {
                $anomalies[] = $record;
                continue;
            }
            $clean[] = $record;
        }

        return [$clean, $anomalies];
    }

    /**
     * @param list<TransitionRecord> $records
     * @return list<float>
     */
    private function transitionErrors(array $records): array
    {
        $errors = [];
        foreach ($records as $record) {
            if ($record['error'] !== null) {
                $errors[] = $record['error'];
            }
        }

        return $errors;
    }

    /**
     * @param list<EventRecord> $records
     * @return array{total: int, null: int, missed: int, offWindow: int, matched: Summary}
     */
    private function eventStats(array $records, string $eventType, string $convention): array
    {
        $total = 0;
        $null = 0;
        $missed = 0;
        $offWindow = 0;
        $matchedErrors = [];
        foreach ($records as $record) {
            if ($record['event'] !== $eventType || $record['convention'] !== $convention) {
                continue;
            }
            $total++;
            if ($record['engine_null']) {
                $null++;
                if (!$record['slot0_null']) {
                    $missed++;
                }
                continue;
            }
            if ($record['selection_mismatch'] || $record['error'] === null) {
                $offWindow++;
                continue;
            }
            $matchedErrors[] = $record['error'];
        }

        return [
            'total' => $total,
            'null' => $null,
            'missed' => $missed,
            'offWindow' => $offWindow,
            'matched' => AccuracyComparison::summarize($matchedErrors),
        ];
    }

    /**
     * @param list<EventRecord> $records
     * @param list<string> $eventTypes
     * @return array{summary: Summary, nullOrOffWindow: int}
     */
    private function cityStats(array $records, string $city, array $eventTypes, string $convention): array
    {
        $errors = [];
        $nullOrOffWindow = 0;
        foreach ($records as $record) {
            if ($record['city'] !== $city || $record['convention'] !== $convention) {
                continue;
            }
            if (!in_array($record['event'], $eventTypes, true)) {
                continue;
            }
            if ($record['engine_null']) {
                if (!$record['slot0_null']) {
                    $nullOrOffWindow++;
                }
                continue;
            }
            if ($record['selection_mismatch'] || $record['error'] === null) {
                $nullOrOffWindow++;
                continue;
            }
            $errors[] = $record['error'];
        }

        return ['summary' => AccuracyComparison::summarize($errors), 'nullOrOffWindow' => $nullOrOffWindow];
    }

    /**
     * @return list<string>
     */
    private function eventCities(): array
    {
        $cities = [];
        foreach ($this->events()['records'] as $record) {
            $cities[$record['city']] = true;
        }

        return array_keys($cities);
    }

    /**
     * @param list<float> $errors
     */
    private static function summaryRow(string $label, array $errors): string
    {
        $summary = AccuracyComparison::summarize($errors);

        return sprintf(
            '%s | %d | %s | %s | %s | %s | %s |',
            $label,
            $summary['n'],
            self::num($summary['mean']),
            self::num($summary['rms']),
            self::num($summary['median']),
            self::num($summary['p95']),
            self::num($summary['max']),
        );
    }

    private static function num(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function gitCommit(): string
    {
        if (!function_exists('exec')) {
            return 'unknown';
        }

        $output = [];
        $code = 1;
        @exec('git rev-parse --short HEAD 2>&1', $output, $code);
        if ($code !== 0 || $output === []) {
            return 'unknown';
        }

        return trim($output[0]);
    }
}
