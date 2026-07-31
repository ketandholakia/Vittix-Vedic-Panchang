<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\Tests\Support\TestLocations;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Fixture-backed regression test suite.
 */
final class FixtureRegressionTest extends TestCase
{
    private Panchang $panchang;
    private GeoLocation $ahmedabad;
    private array $snapshots;

    protected function setUp(): void
    {
        $this->panchang  = Panchang::createDefault();
        $this->ahmedabad = TestLocations::ahmedabad();

        $snapshotFile = __DIR__ . '/../fixtures/generated/panchang_snapshots.json';
        
        self::assertFileExists($snapshotFile, 'Run php scripts/generate_regression_snapshots.php first');

        $data = json_decode(file_get_contents($snapshotFile), true, 512, JSON_THROW_ON_ERROR);
        $this->snapshots = $data['dates'];
    }

    /** @dataProvider snapshotDateProvider */
    public function testAgainstRegressionSnapshots(string $dateStr): void
    {
        $fixture = $this->snapshots[$dateStr];
        $day = $this->computeDay($dateStr);
        $toleranceSeconds = 0; // These are snapshots from the engine, so it should match exactly, but keeping some tolerance if needed.

        // Tithi
        self::assertSame($fixture['tithi']['index'], $day->tithiAtSunrise->value);
        self::assertSame($fixture['tithi']['name_en'], $day->tithiAtSunrise->nameEnglish());
        self::assertSame($fixture['tithi']['paksha'], $day->paksha->label());
        if ($fixture['tithi']['ends_at'] !== null) {
            $this->assertTimestampWithin($fixture['tithi']['ends_at'], $day->tithiEndsAt, $toleranceSeconds, "{$dateStr}: tithi ends at mismatch");
        }

        // Nakshatra
        self::assertSame($fixture['nakshatra']['index'], $day->nakshatraAtSunrise->value);
        self::assertSame($fixture['nakshatra']['name_en'], $day->nakshatraAtSunrise->nameEnglish());
        self::assertSame($fixture['nakshatra']['pada'], $day->padaAtSunrise);
        if ($fixture['nakshatra']['ends_at'] !== null) {
            $this->assertTimestampWithin($fixture['nakshatra']['ends_at'], $day->nakshatraEndsAt, $toleranceSeconds, "{$dateStr}: nakshatra ends at mismatch");
        }

        // Yoga
        self::assertSame($fixture['yoga']['index'], $day->yogaAtSunrise->value);
        self::assertSame($fixture['yoga']['name_en'], $day->yogaAtSunrise->nameEnglish());
        if ($fixture['yoga']['ends_at'] !== null) {
            $this->assertTimestampWithin($fixture['yoga']['ends_at'], $day->yogaEndsAt, $toleranceSeconds, "{$dateStr}: yoga ends at mismatch");
        }

        // Karana
        self::assertSame($fixture['karana']['index'], $day->karanaAtSunrise->value);
        self::assertSame($fixture['karana']['name_en'], $day->karanaAtSunrise->nameEnglish());
        if ($fixture['karana']['ends_at'] !== null) {
            $this->assertTimestampWithin($fixture['karana']['ends_at'], $day->karanaEndsAt, $toleranceSeconds, "{$dateStr}: karana ends at mismatch");
        }

        // Vara
        self::assertSame($fixture['vara']['index'], $day->vara->value);
        self::assertSame($fixture['vara']['name_en'], $day->vara->nameEnglish());

        // Lunar Month
        self::assertSame($fixture['lunar_month']['index'], $day->lunarMonth?->value);
        self::assertSame($fixture['lunar_month']['name_en'], $day->lunarMonth?->nameEnglish());
        self::assertSame($fixture['lunar_month']['is_adhika'], $day->isAdhikaMasa);

        // Solar Events
        if ($fixture['solar_events']['sunrise'] !== null) {
            $this->assertTimestampWithin($fixture['solar_events']['sunrise'], $day->solarEvents->sunrise, $toleranceSeconds, "{$dateStr}: sunrise mismatch");
        }
        if ($fixture['solar_events']['sunset'] !== null) {
            $this->assertTimestampWithin($fixture['solar_events']['sunset'], $day->solarEvents->sunset, $toleranceSeconds, "{$dateStr}: sunset mismatch");
        }
    }

    private function assertTimestampWithin(string $expectedIso, ?DateTimeImmutable $actual, int $toleranceSeconds, string $message): void
    {
        self::assertNotNull($actual, $message . ': actual value is null');

        $expected = new DateTimeImmutable($expectedIso);

        self::assertLessThanOrEqual(
            $toleranceSeconds,
            abs($expected->getTimestamp() - $actual->getTimestamp()),
            $message
        );
    }

    public static function snapshotDateProvider(): iterable
    {
        $fixtureFile = __DIR__ . '/../fixtures/generated/panchang_snapshots.json';
        
        if (!is_file($fixtureFile)) {
            throw new \RuntimeException(
                'Missing regression snapshot: ' . $fixtureFile
            );
        }

        $contents = file_get_contents($fixtureFile);

        if ($contents === false) {
            throw new \RuntimeException(
                'Unable to read regression snapshot: ' . $fixtureFile
            );
        }

        $data = json_decode(
            $contents,
            true,
            flags: JSON_THROW_ON_ERROR
        );

        if (
            !isset($data['dates'])
            || !is_array($data['dates'])
            || $data['dates'] === []
        ) {
            throw new \RuntimeException(
                'Regression snapshot contains no dates.'
            );
        }

        foreach (array_keys($data['dates']) as $date) {
            yield $date => [$date];
        }
    }

    private function computeDay(string $dateStr): \Vittix\Panchang\ValueObject\PanchangDay
    {
        $date = new DateTimeImmutable($dateStr, new DateTimeZone('Asia/Kolkata'));
        return $this->panchang->day($date, $this->ahmedabad);
    }
}
