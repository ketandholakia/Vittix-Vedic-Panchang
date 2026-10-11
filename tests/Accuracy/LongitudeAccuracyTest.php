<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Accuracy;

use PHPUnit\Framework\Attributes\Group;

/**
 * Compares engine longitudes and the Lahiri ayanamsha against Swiss Ephemeris.
 *
 * Errors are signed arcseconds (engine − reference) with shortest-angle
 * wraparound, so a value crossing 0°/360° never shows a ~360° error.
 *
 * @phpstan-import-type LongitudeRecord from AccuracyComparison
 * @phpstan-import-type TimeRecord from AccuracyComparison
 */
#[Group('accuracy')]
final class LongitudeAccuracyTest extends AccuracyTestCase
{
    /**
     * @var array{
     *     samples: int,
     *     tropical: list<LongitudeRecord>,
     *     sidereal: list<LongitudeRecord>,
     *     ayanamsha_mean: list<TimeRecord>,
     *     ayanamsha_true: list<TimeRecord>
     * }|null
     */
    private static ?array $result = null;

    /**
     * @return array{
     *     samples: int,
     *     tropical: list<LongitudeRecord>,
     *     sidereal: list<LongitudeRecord>,
     *     ayanamsha_mean: list<TimeRecord>,
     *     ayanamsha_true: list<TimeRecord>
     * }
     */
    private function comparison(): array
    {
        return self::$result ??= AccuracyComparison::longitudes($this->repo(), $this->engine());
    }

    /**
     * @param list<LongitudeRecord> $records
     * @return array<string, list<float>>
     */
    private static function groupByBody(array $records): array
    {
        $byBody = [];
        foreach ($records as $record) {
            $byBody[$record['body']][] = $record['error'];
        }

        return $byBody;
    }

    /**
     * @param list<LongitudeRecord> $records
     * @return LongitudeRecord|null
     */
    private static function worstForBody(array $records, string $body)
    {
        $worst = null;
        foreach ($records as $record) {
            if ($record['body'] !== $body) {
                continue;
            }
            if ($worst === null || abs($record['error']) > abs($worst['error'])) {
                $worst = $record;
            }
        }

        return $worst;
    }

    public function testTropicalLongitudesWithinTolerance(): void
    {
        $this->skipWithoutFixture('body_longitudes');
        $result = $this->comparison();

        self::assertGreaterThan(0, $result['samples'], 'Fixture contains no samples.');
        self::assertCount($result['samples'] * 8, $result['tropical']);

        foreach (self::groupByBody($result['tropical']) as $body => $errors) {
            $summary = AccuracyComparison::summarize($errors);
            $worst = self::worstForBody($result['tropical'], $body);
            $limit = Tolerances::LONGITUDE_TROPICAL_MAX_ARCSEC[$body]
                ?? Tolerances::LONGITUDE_TROPICAL_MAX_ARCSEC['default'];

            self::assertLessThanOrEqual(
                $limit,
                $summary['max'],
                sprintf(
                    'Tropical longitude error for %s exceeds %.1f": max %.1f" at %s.',
                    $body,
                    $limit,
                    $summary['max'],
                    $worst === null ? 'n/a' : $worst['utc'],
                ),
            );
        }
    }

    public function testSiderealLongitudesWithinTolerance(): void
    {
        $this->skipWithoutFixture('body_longitudes');
        $result = $this->comparison();

        self::assertGreaterThan(0, $result['samples'], 'Fixture contains no samples.');

        foreach (self::groupByBody($result['sidereal']) as $body => $errors) {
            $summary = AccuracyComparison::summarize($errors);
            $worst = self::worstForBody($result['sidereal'], $body);
            $limit = Tolerances::LONGITUDE_SIDEREAL_MAX_ARCSEC[$body]
                ?? Tolerances::LONGITUDE_SIDEREAL_MAX_ARCSEC['default'];

            self::assertLessThanOrEqual(
                $limit,
                $summary['max'],
                sprintf(
                    'Sidereal longitude error for %s exceeds %.1f": max %.1f" at %s.',
                    $body,
                    $limit,
                    $summary['max'],
                    $worst === null ? 'n/a' : $worst['utc'],
                ),
            );
        }
    }

    public function testAyanamshaMeanWithinTolerance(): void
    {
        $this->skipWithoutFixture('body_longitudes');
        $result = $this->comparison();

        $errors = [];
        foreach ($result['ayanamsha_mean'] as $record) {
            $errors[] = $record['error'];
        }
        $summary = AccuracyComparison::summarize($errors);

        self::assertLessThanOrEqual(
            Tolerances::AYANAMSHA_MEAN_MAX_ARCSEC,
            $summary['max'],
            sprintf('Lahiri ayanamsha (geometric) error exceeds %.1f": max %.2f".', Tolerances::AYANAMSHA_MEAN_MAX_ARCSEC, $summary['max']),
        );
    }

    public function testAyanamshaTrueWithinTolerance(): void
    {
        $this->skipWithoutFixture('body_longitudes');
        $result = $this->comparison();

        $errors = [];
        foreach ($result['ayanamsha_true'] as $record) {
            $errors[] = $record['error'];
        }
        $summary = AccuracyComparison::summarize($errors);

        self::assertLessThanOrEqual(
            Tolerances::AYANAMSHA_TRUE_MAX_ARCSEC,
            $summary['max'],
            sprintf('Lahiri ayanamsha (true, used by sidereal calc) error exceeds %.1f": max %.2f".', Tolerances::AYANAMSHA_TRUE_MAX_ARCSEC, $summary['max']),
        );
    }
}
