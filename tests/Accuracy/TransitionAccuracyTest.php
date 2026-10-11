<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Accuracy;

use PHPUnit\Framework\Attributes\Group;

/**
 * Compares limb transition end times (tithi / nakshatra / yoga / karana)
 * against Swiss Ephemeris root-finding results.
 *
 * Errors are signed seconds (engine − reference). Comparisons whose absolute
 * error exceeds TRANSITION_ANOMALY_SECONDS are treated as start-near-boundary
 * races (a different event was picked as "next") and are counted separately
 * instead of being folded into the time-error statistics.
 *
 * @phpstan-import-type TransitionRecord from AccuracyComparison
 */
#[Group('accuracy')]
final class TransitionAccuracyTest extends AccuracyTestCase
{
    /**
     * @var array{samples: int, records: list<TransitionRecord>}|null
     */
    private static ?array $result = null;

    /**
     * @return array{samples: int, records: list<TransitionRecord>}
     */
    private function comparison(): array
    {
        return self::$result ??= AccuracyComparison::transitions($this->repo(), $this->engine());
    }

    public function testLimbTransitionsWithinTolerance(): void
    {
        $this->skipWithoutFixture('transitions');
        $result = $this->comparison();

        self::assertGreaterThan(0, $result['samples'], 'Fixture contains no samples.');

        $byLimb = [];
        foreach ($result['records'] as $record) {
            $byLimb[$record['limb']][] = $record;
        }

        foreach (AccuracyComparison::LIMBS as $limb) {
            $records = $byLimb[$limb] ?? [];
            self::assertGreaterThan(0, count($records), "No comparisons for limb '{$limb}'.");

            $missing = 0;
            $anomalies = [];
            $errors = [];

            foreach ($records as $record) {
                if ($record['error'] === null) {
                    $missing++;
                    continue;
                }
                if (abs($record['error']) > Tolerances::TRANSITION_ANOMALY_SECONDS) {
                    $anomalies[] = $record;
                    continue;
                }
                $errors[] = $record['error'];
            }

            self::assertSame(
                0,
                $missing,
                sprintf("Limb '%s': engine failed to find a transition within 5 days.", $limb),
            );

            self::assertLessThanOrEqual(
                Tolerances::TRANSITION_MAX_ANOMALIES,
                count($anomalies),
                sprintf(
                    "Limb '%s': %d anomalous comparisons (> %.0fs apart). First: %s.",
                    $limb,
                    count($anomalies),
                    Tolerances::TRANSITION_ANOMALY_SECONDS,
                    isset($anomalies[0]) ? $anomalies[0]['start_utc'] : 'n/a',
                ),
            );

            $summary = AccuracyComparison::summarize($errors);

            self::assertLessThanOrEqual(
                Tolerances::TRANSITION_MAX_SECONDS,
                $summary['max'],
                sprintf(
                    "Limb '%s' transition error exceeds %.0fs: max %.1fs (mean %.1fs, p95 %.1fs).",
                    $limb,
                    Tolerances::TRANSITION_MAX_SECONDS,
                    $summary['max'],
                    $summary['mean'],
                    $summary['p95'],
                ),
            );
        }
    }
}
