<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Accuracy;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Enum\Kala;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Festivals\Festival;
use Vittix\Panchang\Festivals\FestivalCatalog;
use Vittix\Panchang\Festivals\FestivalDayContext;
use Vittix\Panchang\Festivals\FestivalEngine;
use Vittix\Panchang\Festivals\Rules\RakshaBandhanRule;
use Vittix\Panchang\Panchanga\KaranaCalculator;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\Tests\Support\TestLocations;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Held-out-years check for the festival engine (2015–2022, 2028–2030).
 *
 * These years were NOT used for rule development. drikpanchang began
 * blocking this environment (403/captcha) mid-session, so only a subset
 * of the 88 possible (festival, year) pairs could be verified — the rest
 * are recorded as "unavailable" in the fixture and skipped.
 *
 * Raksha Bandhan entries carry Purnima/Bhadra timings; the deciding tier
 * (1/2/3 from RakshaBandhanRule) is derived from the rule's score and
 * printed per year.
 */
#[Group('accuracy')]
#[CoversClass(FestivalEngine::class)]
#[CoversClass(RakshaBandhanRule::class)]
final class FestivalHoldoutTest extends AccuracyTestCase
{
    private ?FestivalEngine $engine = null;

    /** @var array<string, Festival> */
    private array $catalog = [];

    protected function setUp(): void
    {
        $astronomy = new PurePhpAstronomyEngine();
        $lunarMonth = new LunarMonthCalculator($astronomy);
        $this->engine = new FestivalEngine($astronomy, new TithiCalculator($astronomy), $lunarMonth);
        foreach (FestivalCatalog::getDefaultFestivals($astronomy) as $festival) {
            $this->catalog[$festival->name] = $festival;
            $this->engine->addFestival($festival);
        }
    }

    private function festivalEngine(): FestivalEngine
    {
        return $this->engine ?? throw new \LogicException('setUp not called');
    }

    /**
     * Raksha Bandhan holdout: resolve each available held-out year,
     * print engine/drik/tier, pass if >= 90% match drik.
     */
    public function testRakshaBandhanHoldoutMatchesDrik(): void
    {
        $this->skipWithoutFixture('festival_dates_holdout');

        $tz = new DateTimeZone('Asia/Kolkata');
        $festival = $this->catalog['Raksha Bandhan'] ?? throw new \LogicException('Raksha Bandhan not in catalog');

        $match = 0;
        $total = 0;
        $mismatches = [];
        $lines = [];

        foreach ($this->repo()->samples('festival_dates_holdout') as $sample) {
            if (($sample['festival'] ?? '') !== 'Raksha Bandhan') {
                continue;
            }
            if (isset($sample['unavailable'])) {
                $lines[] = sprintf('%d: UNAVAILABLE (%s)', $sample['year'], $sample['unavailable']);
                continue;
            }

            $total++;
            $expected = (string) $sample['date'];
            $resolved = $this->festivalEngine()->resolve($festival, (int) $sample['year'], TestLocations::delhi(), $tz);
            $actual = $resolved?->format('Y-m-d') ?? 'NOT RESOLVED';
            $tier = $this->decidingTier($festival, $resolved, TestLocations::delhi(), $tz);

            if ($actual === $expected) {
                $match++;
            } else {
                $mismatches[] = sprintf(
                    '%d: drik=%s engine=%s tier=%s | %s',
                    $sample['year'],
                    $expected,
                    $actual,
                    $tier,
                    (string) ($sample['drik_page_facts'] ?? '')
                );
            }
            $lines[] = sprintf('%d: engine=%s drik=%s tier=%s %s', $sample['year'], $actual, $expected, $tier, $actual === $expected ? 'MATCH' : 'MISMATCH');
        }

        printf("\nRaksha Bandhan holdout: %d/%d exact (%.0f%%)\n%s\n%s\n",
            $match, $total, $total > 0 ? 100.0 * $match / $total : 0.0,
            implode("\n", $lines),
            $mismatches !== [] ? "MISMATCHES:\n" . implode("\n", $mismatches) : 'No mismatches.'
        );

        $rate = $total > 0 ? $match / $total : 1.0;
        self::assertGreaterThanOrEqual(
            0.90,
            $rate,
            sprintf(
                "Raksha Bandhan holdout match rate %.0f%% < 90%%.\n%s",
                100.0 * $rate,
                implode("\n", $mismatches)
            )
        );
    }

    /**
     * The other 7 catalog festivals: date comparison for the available
     * holdout years; per-festival exact-match rates are printed. All
     * divergences must be documented in known_divergences.
     */
    public function testOtherFestivalsHoldout(): void
    {
        $this->skipWithoutFixture('festival_dates_holdout');

        $tz = new DateTimeZone('Asia/Kolkata');
        $rates = [];
        $allUnexpected = [];

        /** @var array<string, array{match: int, total: int}> $perFestival */
        $perFestival = [];

        $holdoutMeta = $this->repo()->metadata('festival_dates_holdout');
        $holdoutDivergences = [];
        foreach ($holdoutMeta['known_divergences'] ?? [] as $div) {
            $holdoutDivergences[sprintf('%s|%d', $div['festival'], $div['year'])] = true;
        }

        foreach ($this->repo()->samples('festival_dates_holdout') as $sample) {
            $name = (string) $sample['festival'];
            if ($name === 'Raksha Bandhan') {
                continue; // covered by the dedicated test
            }
            if (isset($sample['unavailable'])) {
                continue;
            }

            $festival = $this->catalog[$name] ?? null;
            if ($festival === null) {
                $allUnexpected[] = "$name {$sample['year']}: not in the catalog";
                continue;
            }

            $perFestival[$name]['total'] = ($perFestival[$name]['total'] ?? 0) + 1;
            $resolved = $this->festivalEngine()->resolve(
                $festival,
                (int) $sample['year'],
                TestLocations::delhi(),
                $tz
            );
            $expected = (string) $sample['date'];
            $actual = $resolved?->format('Y-m-d') ?? 'NOT RESOLVED';

            if ($actual === $expected) {
                $perFestival[$name]['match'] = ($perFestival[$name]['match'] ?? 0) + 1;
            } elseif (isset($holdoutDivergences[sprintf('%s|%d', $name, (int) $sample['year'])])) {
                // Documented divergence — not unexpected.
            } else {
                $allUnexpected[] = sprintf(
                    '%s %d: drikpanchang=%s, engine=%s (undocumented holdout disagreement)',
                    $name,
                    (int) $sample['year'],
                    $expected,
                    $actual
                );
            }
        }

        foreach ($perFestival as $name => $stats) {
            $m = $stats['match'] ?? 0;
            $t = $stats['total'];
            $rates[] = sprintf('%s: %d/%d exact', $name, $m, $t);
        }
        printf("\nHeld-out per-festival rates:\n%s\n", implode("\n", $rates) ?: '(no data)');

        self::assertSame([], $allUnexpected, implode("\n", $allUnexpected));
    }

    /**
     * Which tier decided for the given festival on the resolved day:
     * score >= 3 → tier 1; 2..3 → tier 2; 1..2 → tier 3; 0..1 → fallback.
     */
    private function decidingTier(Festival $festival, ?DateTimeImmutable $resolved, GeoLocation $location, DateTimeZone $tz): string
    {
        if ($resolved === null) {
            return 'n/a';
        }

        $rule = $festival->rule;
        if (!$rule instanceof RakshaBandhanRule) {
            return 'n/a';
        }

        $astronomy = new PurePhpAstronomyEngine();
        $lunarMonth = new LunarMonthCalculator($astronomy);
        $day = $resolved->setTime(0, 0);
        $se = $astronomy->solarEvents($day, $location, $tz);
        $ns = $astronomy->solarEvents($day->modify('+1 day'), $location, $tz);
        $context = new FestivalDayContext(
            $day,
            $tz,
            $se->sunrise,
            $se->sunset,
            $ns->sunrise,
            new \Vittix\Panchang\Festivals\TithiTimeline($astronomy, new TithiCalculator($astronomy)),
            $lunarMonth
        );

        $score = $rule->score($context);
        if ($score >= 3.0) {
            return '1';
        }
        if ($score >= 2.0) {
            return '2';
        }
        if ($score >= 1.0) {
            return '3';
        }

        return 'fallback';
    }
}
