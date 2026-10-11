<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Accuracy;

use PHPUnit\Framework\Attributes\Group;

/**
 * Compares sunrise/sunset and moonrise/moonset times against Swiss Ephemeris
 * rise_trans results, for both stored conventions:
 *
 *   • upper_limb_refraction      (rise_trans default)
 *   • disc_center_no_refraction  (BIT_DISC_CENTER | BIT_NO_REFRACTION)
 *
 * The tests assert against the convention recorded in Tolerances; the full
 * report shows both. For moon events, the engine's event-selection window can
 * pick a neighbouring local day's event; the comparator matches against
 * local dates D-1 / D / D+1 and reports the selection offset separately.
 *
 * @phpstan-import-type EventRecord from AccuracyComparison
 */
#[Group('accuracy')]
final class SunMoonEventAccuracyTest extends AccuracyTestCase
{
    /**
     * @var array{samples: int, records: list<EventRecord>}|null
     */
    private static ?array $result = null;

    /**
     * @return array{samples: int, records: list<EventRecord>}
     */
    private function comparison(): array
    {
        return self::$result ??= AccuracyComparison::events($this->repo(), $this->engine());
    }

    /**
     * @param list<EventRecord> $records
     * @return list<EventRecord>
     */
    private static function matched(array $records): array
    {
        $matched = [];
        foreach ($records as $record) {
            if ($record['error'] !== null) {
                $matched[] = $record;
            }
        }

        return $matched;
    }

    /**
     * @param list<EventRecord> $records
     * @return EventRecord|null
     */
    private static function worstCase(array $records)
    {
        $worst = null;
        foreach ($records as $record) {
            if ($record['error'] === null) {
                continue;
            }
            if ($worst === null || abs($record['error']) > abs((float) $worst['error'])) {
                $worst = $record;
            }
        }

        return $worst;
    }

    /**
     * @param list<EventRecord> $records
     * @return array{total: int, null: int, missed: int, selection: int, moved: int}
     */
    private static function counts(array $records): array
    {
        $counts = ['total' => 0, 'null' => 0, 'missed' => 0, 'selection' => 0, 'moved' => 0];
        foreach ($records as $record) {
            $counts['total']++;
            if ($record['engine_null']) {
                $counts['null']++;
                if (!$record['slot0_null']) {
                    $counts['missed']++;
                }
                continue;
            }
            if ($record['selection_mismatch']) {
                $counts['selection']++;
                continue;
            }
            if ($record['matched_offset'] !== 0) {
                $counts['moved']++;
            }
        }

        return $counts;
    }

    public function testSunEventsWithinTolerance(): void
    {
        $this->skipWithoutFixture('sun_moon_events');
        $result = $this->comparison();

        $records = [];
        foreach ($result['records'] as $record) {
            if ($record['convention'] !== Tolerances::SUN_EVENT_CONVENTION) {
                continue;
            }
            if ($record['event'] === 'sunrise' || $record['event'] === 'sunset') {
                $records[] = $record;
            }
        }

        $counts = self::counts($records);
        self::assertGreaterThan(0, $counts['total'], 'No sun event comparisons found.');

        $matched = self::matched($records);
        self::assertGreaterThan(0, count($matched), 'No sun events could be matched.');

        foreach ($matched as $record) {
            self::assertLessThanOrEqual(
                Tolerances::SUN_EVENT_MAX_SECONDS,
                abs((float) $record['error']),
                sprintf(
                    'Sun %s for %s on %s differs by %.1fs (> %.0fs).',
                    $record['event'],
                    $record['city'],
                    $record['date'],
                    abs((float) $record['error']),
                    Tolerances::SUN_EVENT_MAX_SECONDS,
                ),
            );
        }

        self::assertLessThanOrEqual(
            Tolerances::SUN_EVENT_MAX_MISSED,
            $counts['missed'],
            sprintf('Engine returned null for %d sun events that the reference has.', $counts['missed']),
        );
        self::assertLessThanOrEqual(
            Tolerances::SUN_EVENT_MAX_UNMATCHED,
            $counts['selection'],
            sprintf('%d sun events were placed outside the match window.', $counts['selection']),
        );
    }

    public function testMoonEventsWithinTolerance(): void
    {
        $this->skipWithoutFixture('sun_moon_events');
        $result = $this->comparison();

        $records = [];
        foreach ($result['records'] as $record) {
            if ($record['convention'] !== Tolerances::MOON_EVENT_CONVENTION) {
                continue;
            }
            if ($record['event'] === 'moonrise' || $record['event'] === 'moonset') {
                $records[] = $record;
            }
        }

        $counts = self::counts($records);
        self::assertGreaterThan(0, $counts['total'], 'No moon event comparisons found.');

        $matched = self::matched($records);
        self::assertGreaterThan(0, count($matched), 'No moon events could be matched.');

        $worst = self::worstCase($matched);
        $summary = AccuracyComparison::summarize(array_map(
            static function (array $record): float {
                /** @var array{error: float|null} $record */
                return (float) $record['error'];
            },
            $matched,
        ));

        self::assertLessThanOrEqual(
            Tolerances::MOON_EVENT_MAX_SECONDS,
            $summary['max'],
            sprintf(
                'Moon event error exceeds %.0fs: max %.1fs (mean %.1fs). Worst: %s %s (%s) by %.1fs.',
                Tolerances::MOON_EVENT_MAX_SECONDS,
                $summary['max'],
                $summary['mean'],
                $worst === null ? 'n/a' : $worst['city'],
                $worst === null ? 'n/a' : $worst['date'],
                $worst === null ? 'n/a' : $worst['event'],
                $worst === null ? 0.0 : abs((float) $worst['error']),
            ),
        );

        self::assertLessThanOrEqual(
            Tolerances::MOON_EVENT_MAX_MISSED,
            $counts['missed'],
            sprintf('Engine returned null for %d moon events that the reference has.', $counts['missed']),
        );
        self::assertLessThanOrEqual(
            Tolerances::MOON_EVENT_MAX_SELECTION_MISMATCH,
            $counts['selection'],
            sprintf('%d moon events were placed outside the match window (neighbouring day).', $counts['selection']),
        );
        self::assertLessThanOrEqual(
            Tolerances::MOON_EVENT_MAX_MOVED,
            $counts['moved'],
            sprintf('%d moon events matched a neighbouring day within the match window.', $counts['moved']),
        );
    }
}
