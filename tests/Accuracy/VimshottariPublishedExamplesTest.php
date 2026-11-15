<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Accuracy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astrology\VimshottariDashaCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Time\AstroTimeFactory;

/**
 * Vimshottari cross-check against published worked examples and an
 * independently written 120-year table + 27-nakshatra lord mapping.
 *
 * ⚠️ NOT an independent oracle: the test shares the engine's year length
 * (365.2425 days) and reporting conventions. It catches regressions in
 * the table, the nakshatra→lord mapping, and the balance arithmetic, but
 * it cannot detect a wrong year-length convention.
 *
 * Published worked examples with ABSOLUTE calendar dates could not be
 * found from citable sources in this environment (the Gandhi chart has
 * a disputed birth time; the online guides give Moon longitude and
 * balance fraction but no birth-date-to-calendar-date walkthrough).
 * The examples below therefore assert the balance FRACTION, which is
 * convention-independent.
 */
#[Group('accuracy')]
#[CoversClass(VimshottariDashaCalculator::class)]
final class VimshottariPublishedExamplesTest extends AccuracyTestCase
{
    /** Independently written 120-year table (NOT read from the engine). */
    private const LORD_YEARS = [
        'ketu_mean' => 7, 'venus' => 20, 'sun' => 6, 'moon' => 10,
        'mars' => 7, 'rahu_mean' => 18, 'jupiter' => 16,
        'saturn' => 19, 'mercury' => 17,
    ];

    /** Independently written nakshatra→lord mapping (all 27). */
    private const NAKSHATRA_LORDS = [
        'Ashwini' => 'ketu_mean', 'Bharani' => 'venus', 'Krittika' => 'sun',
        'Rohini' => 'moon', 'Mrigashira' => 'mars', 'Ardra' => 'rahu_mean',
        'Punarvasu' => 'jupiter', 'Pushya' => 'saturn', 'Ashlesha' => 'mercury',
        'Magha' => 'ketu_mean', 'PurvaPhalguni' => 'venus', 'UttaraPhalguni' => 'sun',
        'Hasta' => 'moon', 'Chitra' => 'mars', 'Swati' => 'rahu_mean',
        'Vishakha' => 'jupiter', 'Anuradha' => 'saturn', 'Jyeshtha' => 'mercury',
        'Mula' => 'ketu_mean', 'PurvaAshadha' => 'venus', 'UttaraAshadha' => 'sun',
        'Shravana' => 'moon', 'Dhanishta' => 'mars', 'Shatabhisha' => 'rahu_mean',
        'PurvaBhadrapada' => 'jupiter', 'UttaraBhadrapada' => 'saturn', 'Revati' => 'mercury',
    ];

    /**
     * @return array<string, mixed>
     */
    private function fixtureData(): array
    {
        $this->skipWithoutFixture('vimshottari_examples');
        $raw = file_get_contents($this->repo()->path('vimshottari_examples'));
        self::assertNotFalse($raw);
        $data = json_decode($raw, true);
        self::assertIsArray($data);

        return $data;
    }

    public function testMahadashaTableTotals120Years(): void
    {
        $data = $this->fixtureData();
        $table = $data['mahadasha_years'];

        $total = array_sum($table);
        self::assertSame(120, $total, '120-year Vimshottari cycle');

        // The independent table must match the fixture table.
        foreach ($table as $lord => $years) {
            self::assertSame(
                self::LORD_YEARS[$lord],
                $years,
                "lord $years years"
            );
        }
    }

    public function testNakshatraLordMappingForAll27(): void
    {
        $data = $this->fixtureData();
        $fixtureLords = $data['nakshatra_lords'];

        self::assertCount(27, $fixtureLords, '27 nakshatras');
        self::assertCount(27, self::NAKSHATRA_LORDS, 'independent list must cover all 27');

        $calculator = new VimshottariDashaCalculator();
        $birth = new \DateTimeImmutable('2000-01-01 12:00', new \DateTimeZone('UTC'));
        $span = 360.0 / 27.0;

        $i = 0;
        foreach ($fixtureLords as $nakshatraName => $expectedLord) {
            // Moon at the midpoint of this nakshatra.
            $moonLongitude = $i * $span + $span / 2.0;
            $dashas = $calculator->calculate($birth, $moonLongitude);

            self::assertSame(
                Body::from($expectedLord),
                $dashas[0]->body,
                "nakshatra $i ($nakshatraName) must be ruled by $expectedLord"
            );

            // The independent list must match the fixture list.
            self::assertSame(
                self::NAKSHATRA_LORDS[$nakshatraName],
                $expectedLord,
                "independent list vs fixture for $nakshatraName"
            );

            $i++;
        }
    }

    public function testPublishedWorkedExamples(): void
    {
        $data = $this->fixtureData();
        $examples = $data['examples'] ?? [];

        self::assertGreaterThanOrEqual(
            3,
            count($examples),
            'At least 3 worked examples recorded (fewer than 6 citable examples with published calendar dates could be found)'
        );

        $calculator = new VimshottariDashaCalculator();
        $birth = new \DateTimeImmutable('1990-05-05 10:00', new \DateTimeZone('Asia/Kolkata'));

        foreach ($data['examples'] ?? [] as $example) {
            $moonLon = (float) $example['moon_sidereal_longitude'];
            $expectedLord = (string) $example['expected_first_lord'];
            $expectedBalance = (float) $example['expected_balance_fraction'];

            $dashas = $calculator->calculate($birth, $moonLon);

            self::assertSame(
                Body::from($expectedLord),
                $dashas[0]->body,
                "{$example['name']}: first Mahadasha lord"
            );

            $fullYears = self::LORD_YEARS[$expectedLord];
            $actualFraction = ($dashas[0]->end->getTimestamp() - $dashas[0]->start->getTimestamp())
                / ($fullYears * self::YEAR_SECONDS());

            self::assertEqualsWithDelta(
                $expectedBalance,
                $actualFraction,
                0.001,
                "{$example['name']}: balance fraction"
            );
        }
    }

    private static function YEAR_SECONDS(): float
    {
        return 31556952.0; // 365.2425 days — the engine's documented convention
    }
}
