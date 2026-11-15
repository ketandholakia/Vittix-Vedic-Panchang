<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Accuracy;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astrology\VimshottariDashaCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Tests\Support\TestLocations;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Vimshottari oracle — closed-form boundaries computed IN THE TEST from
 * the Swiss-Ephemeris sidereal Moon longitude (body_longitudes.json
 * fixture), fully independently of VimshottariDashaCalculator:
 *
 *   nakshatra span = 360°/27; the birth lord is SEQUENCE[floor(lon/span) % 9];
 *   balance = (1 − fraction elapsed) × lord years; the first Mahadasha
 *   starts at birth minus the elapsed fraction; Antardashas subdivide the
 *   FULL Mahadasha from its true start at (years × lordYears / 120).
 *
 * Tolerance: 1 minute (the engine rounds each period to whole seconds).
 *
 * Locations are irrelevant to Vimshottari (it uses only the birth instant
 * and the Moon's sidereal longitude); they are still recorded per the
 * oracle spec.
 */
#[Group('accuracy')]
#[CoversClass(VimshottariDashaCalculator::class)]
final class VimshottariClosedFormTest extends AccuracyTestCase
{
    private const TOLERANCE_SECONDS = 60;

    /** The 120-year table, independent copy. */
    private const LORD_YEARS = [
        'ketu_mean' => 7.0,
        'venus' => 20.0,
        'sun' => 6.0,
        'moon' => 10.0,
        'mars' => 7.0,
        'rahu_mean' => 18.0,
        'jupiter' => 16.0,
        'saturn' => 19.0,
        'mercury' => 17.0,
    ];

    private const SEQUENCE = [
        'ketu_mean', 'venus', 'sun', 'moon', 'mars', 'rahu_mean', 'jupiter', 'saturn', 'mercury',
    ];

    private const YEAR_SECONDS = 31556952.0; // 365.2425 days — same convention the engine documents

    /**
     * 20 deterministic pseudo-random birth instants drawn from the Swiss
     * fixture's first 2000 samples via a fixed stride.
     *
     * @return list<array{0: DateTimeImmutable, 1: float, 2: GeoLocation}>
     */
    private function births(): array
    {
        $samples = $this->repo()->samples('body_longitudes');
        self::assertGreaterThanOrEqual(200, count($samples));

        $births = [];
        $locations = [
            [23.0225, 72.5714],
            [28.6139, 77.2090],
            [40.7128, -74.0060],
            [-33.8688, 151.2093],
        ];

        for ($i = 0; $i < 20; $i++) {
            $sample = $samples[$i * 100];
            $births[] = [
                new DateTimeImmutable((string) $sample['utc'], new DateTimeZone('UTC')),
                (float) $sample['bodies']['moon']['sidereal'],
                new GeoLocation(
                    $locations[$i % 4][0],
                    $locations[$i % 4][1],
                    0.0
                ),
            ];
        }

        return $births;
    }

    /**
     * Closed-form Mahadasha list: [lord, startTs, endTs, trueStartTs].
     *
     * Reporting convention (the engine's documented one): the first
     * Mahadasha is reported with its VISIBLE start at birth — its true
     * start (birth minus the elapsed balance) is exercised through the
     * Antardasha timeline instead, which the classical rules derive from
     * the true start. The dasha CHANGE boundaries (MD0.end = MD1.start
     * etc.) are identical under both conventions.
     *
     * @return list<array{0: string, 1: int, 2: int, 3: int}>
     */
    private function closedFormMahadashas(float $moonSidereal, int $birthTs): array
    {
        $span = 360.0 / 27.0;
        $position = $moonSidereal / $span;
        $index = (int) floor($position);
        $fractionElapsed = $position - $index;

        $startSeq = $index % 9;
        $lord = self::SEQUENCE[$startSeq];
        $lordYears = self::LORD_YEARS[$lord];

        // Balance: the Mahadasha began before birth.
        $elapsedSeconds = (int) round($lordYears * $fractionElapsed * self::YEAR_SECONDS);
        $trueStart = $birthTs - $elapsedSeconds;

        $out = [];
        $cursor = $birthTs; // visible start of the first Mahadasha
        for ($i = 0; $i < 9; $i++) {
            $seqLord = self::SEQUENCE[($startSeq + $i) % 9];
            $years = ($i === 0) ? $lordYears * (1.0 - $fractionElapsed) : self::LORD_YEARS[$seqLord];
            $seconds = (int) round($years * self::YEAR_SECONDS);
            $out[] = [$seqLord, $cursor, $cursor + $seconds, $trueStart];
            $cursor += $seconds;
        }

        return $out;
    }

    /**
     * Closed-form Antardashas of one Mahadasha: full-length sub-periods
     * from the Mahadasha's true start at (years × lordYears / 120), parts
     * before the visible start dropped, the running one truncated.
     *
     * @return list<array{0: string, 1: int, 2: int}>
     */
    private function closedFormAntardashas(
        string $mahadashaLord,
        float $mahadashaYears,
        int $trueStart,
        int $visibleStart,
        int $visibleEnd
    ): array {
        $startIdx = (int) array_search($mahadashaLord, self::SEQUENCE, true);
        $fullSeconds = (int) round($mahadashaYears * self::YEAR_SECONDS);

        $out = [];
        $cursor = $trueStart;
        for ($i = 0; $i < 9; $i++) {
            $lord = self::SEQUENCE[($startIdx + $i) % 9];
            $seconds = (int) round(self::LORD_YEARS[$lord] * $mahadashaYears / 120.0 * self::YEAR_SECONDS);
            $end = $cursor + $seconds;
            if ($i === 8) {
                $end = $trueStart + $fullSeconds;
            }

            if ($end > $visibleStart) {
                $out[] = [
                    $lord,
                    max($cursor, $visibleStart),
                    min($end, $visibleEnd),
                ];
            }

            $cursor = $end;
        }

        return $out;
    }

    public function testMahadashasMatchClosedForm(): void
    {
        $calculator = new VimshottariDashaCalculator();

        foreach ($this->births() as $n => [$birth, $moonSidereal, $location]) {
            $dashas = $calculator->calculate($birth, $moonSidereal);
            $expected = $this->closedFormMahadashas($moonSidereal, $birth->getTimestamp());

            self::assertCount(9, $dashas, "birth #$n");

            foreach ($expected as $i => [$lord, $startTs, $endTs, $trueStartTs]) {
                self::assertSame(
                    Body::from($lord),
                    $dashas[$i]->body,
                    "birth #$n Mahadasha $i"
                );
                self::assertLessThanOrEqual(
                    self::TOLERANCE_SECONDS,
                    abs($dashas[$i]->start->getTimestamp() - $startTs),
                    "birth #$n Mahadasha $i start"
                );
                self::assertLessThanOrEqual(
                    self::TOLERANCE_SECONDS,
                    abs($dashas[$i]->end->getTimestamp() - $endTs),
                    "birth #$n Mahadasha $i end"
                );
            }
        }
    }

    /**
     * Two classic textbook-style worked examples.
     *
     * (1) Moon at 2° Taurus = 32° sidereal → Krittika (26°40′–40°00′),
     *     5°20′ traversed of 13°20′ = 40% elapsed → Sun Mahadasha with a
     *     balance of 60% of 6 years = 3.6 years. (The standard illustration
     *     reproduced e.g. in the "Module 3 — Calculation of Vimshottari
     *     Dasha" teaching module and on DashaClub's dasha guide; no
     *     page-cited print textbook could be verified in this environment,
     *     so the balance FRACTION is asserted rather than absolute dates —
     *     textbook y/m/d conversions use 360-day years and would not match
     *     the engine's 365.2425-day year to 1-minute precision.)
     *
     * (2) Moon at 0° sidereal → Ashwini pada 1 → Ketu Mahadasha with the
     *     FULL 7-year balance (the canonical starting-point example).
     */
    public function testTextbookWorkedExamples(): void
    {
        $calculator = new VimshottariDashaCalculator();
        $birth = new DateTimeImmutable('1990-05-05 10:00', new DateTimeZone('Asia/Kolkata'));

        // (1) Krittika 40% elapsed.
        $dashas = $calculator->calculate($birth, 32.0);
        self::assertSame(Body::Sun, $dashas[0]->body, 'Krittika is ruled by the Sun');
        $fullSunSeconds = 6.0 * self::YEAR_SECONDS;
        $balanceFraction = ($dashas[0]->end->getTimestamp() - $dashas[0]->start->getTimestamp()) / $fullSunSeconds;
        self::assertEqualsWithDelta(0.6, $balanceFraction, 0.001, 'Krittika 40% traversed -> 60% Sun balance');
        self::assertSame(Body::Moon, $dashas[1]->body, 'Sun is followed by Moon');

        // (2) Ashwini start — full Ketu balance.
        $dashas = $calculator->calculate($birth, 0.0);
        self::assertSame(Body::KetuMean, $dashas[0]->body, 'Ashwini is ruled by Ketu');
        self::assertSame(
            (int) round(7.0 * self::YEAR_SECONDS),
            $dashas[0]->end->getTimestamp() - $dashas[0]->start->getTimestamp(),
            'Ashwini start -> full 7-year Ketu balance'
        );
        self::assertSame(Body::Venus, $dashas[1]->body, 'Ketu is followed by Venus');
    }

    public function testAntardashasMatchClosedForm(): void
    {
        $calculator = new VimshottariDashaCalculator();

        foreach ($this->births() as $n => [$birth, $moonSidereal, $location]) {
            $dashas = $calculator->calculate($birth, $moonSidereal);
            $mahadashas = $this->closedFormMahadashas($moonSidereal, $birth->getTimestamp());

            foreach ($mahadashas as $i => [$lord, $startTs, $endTs, $trueStartTs]) {
                $expectedAntars = $this->closedFormAntardashas(
                    $lord,
                    self::LORD_YEARS[$lord],
                    $i === 0 ? $trueStartTs : $startTs, // first MD: classical true start
                    $startTs,
                    $endTs
                );
                $actualAntars = $dashas[$i]->subPeriods;

                self::assertSame(
                    count($expectedAntars),
                    count($actualAntars),
                    "birth #$n Mahadasha $i ({$lord}) Antardasha count"
                );

                foreach ($expectedAntars as $j => [$aLord, $aStart, $aEnd]) {
                    self::assertSame(Body::from($aLord), $actualAntars[$j]->body, "birth #$n MD $i AD $j lord");
                    self::assertLessThanOrEqual(
                        self::TOLERANCE_SECONDS,
                        abs($actualAntars[$j]->start->getTimestamp() - $aStart),
                        "birth #$n MD $i AD $j start"
                    );
                    self::assertLessThanOrEqual(
                        self::TOLERANCE_SECONDS,
                        abs($actualAntars[$j]->end->getTimestamp() - $aEnd),
                        "birth #$n MD $i AD $j end"
                    );
                }
            }
        }
    }
}
