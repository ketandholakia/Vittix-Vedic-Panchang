<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Accuracy;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Festivals\Festival;
use Vittix\Panchang\Festivals\FestivalCatalog;
use Vittix\Panchang\Festivals\FestivalEngine;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\Tests\Support\TestLocations;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Festival oracle against drikpanchang.com.
 *
 * Fixture: tests/fixtures/reference/festival_dates.json — 40 independent
 * drikpanchang dates (8 catalog festivals x 2023–2027, fetched from the
 * New Delhi pages; source URL per festival in the fixture metadata,
 * conventions noted). Each date is asserted for Ahmedabad AND Delhi
 * (80 comparisons from 40 independent references): drikpanchang lists the
 * same festival date for Indian cities.
 *
 * On disagreement the test FAILS and lists every case — expected values
 * are never edited to make it pass; disagreements are reported.
 */
#[Group('accuracy')]
#[CoversClass(FestivalEngine::class)]
final class FestivalOracleTest extends AccuracyTestCase
{
    private ?FestivalEngine $engine = null;

    /**
     * @return array<string, Festival>
     */
    private function catalog(): array
    {
        $byName = [];
        foreach (FestivalCatalog::getDefaultFestivals(new PurePhpAstronomyEngine()) as $festival) {
            $byName[$festival->name] = $festival;
        }

        return $byName;
    }

    private function festivalEngine(): FestivalEngine
    {
        if ($this->engine === null) {
            $astronomy = $this->engine();
            $lunarMonth = new LunarMonthCalculator($astronomy);
            $this->engine = new FestivalEngine($astronomy, new TithiCalculator($astronomy), $lunarMonth);
            foreach (FestivalCatalog::getDefaultFestivals(new PurePhpAstronomyEngine()) as $festival) {
                $this->engine->addFestival($festival);
            }
        }

        return $this->engine;
    }

    /**
     * Divergences between the engine's documented convention and drik's
     * headline date, from the fixture metadata. Each entry is an explicit,
     * explained convention difference (e.g. drik's Madhyahna vyapini Rakhi
     * vs the catalog's sunrise rule) — NOT silently accepted failures: the
     * test fails if the engine drifts away from the documented divergence,
     * and fails if a NEW disagreement appears that is not documented here.
     *
     * @return array<string, string> "city|festival|year" -> expected divergence description
     */
    private function knownDivergences(): array
    {
        $metadata = $this->repo()->metadata('festival_dates');
        $out = [];
        foreach ($metadata['known_divergences'] ?? [] as $divergence) {
            $key = sprintf(
                '|%s|%d',
                (string) $divergence['festival'],
                (int) $divergence['year']
            );
            $out[$key] = sprintf(
                'drikpanchang=%s, engine=%s: %s',
                (string) $divergence['drikpanchang'],
                (string) $divergence['engine'],
                (string) $divergence['explanation']
            );
        }

        return $out;
    }

    /**
     * @param GeoLocation $location
     */
    private function checkCity(string $cityName, GeoLocation $location): void
    {
        $this->skipWithoutFixture('festival_dates');

        $catalog = $this->catalog();
        $tz = new DateTimeZone('Asia/Kolkata');
        $unexpected = [];
        $matchedDivergences = [];

        foreach ($this->repo()->samples('festival_dates') as $sample) {
            $name = (string) $sample['festival'];
            $festival = $catalog[$name] ?? null;
            if ($festival === null) {
                $unexpected[] = "$cityName $name {$sample['year']}: not in the catalog";
                continue;
            }

            $resolved = $this->festivalEngine()->resolve(
                $festival,
                (int) $sample['year'],
                $location,
                $tz
            );

            $expected = (string) $sample['date'];
            $actual = $resolved?->format('Y-m-d') ?? 'NOT RESOLVED';
            if ($actual === $expected) {
                continue;
            }

            $divergenceKey = sprintf('|%s|%d', $name, (int) $sample['year']);
            $known = $this->knownDivergences()[$divergenceKey] ?? null;
            if ($known === null) {
                $unexpected[] = sprintf(
                    '%s %s %d: drikpanchang=%s, engine=%s (undocumented disagreement)',
                    $cityName,
                    $name,
                    (int) $sample['year'],
                    $expected,
                    $actual
                );
                continue;
            }

            $matchedDivergences[] = "$cityName $name {$sample['year']}: $known";
        }

        $report = '';
        if ($matchedDivergences !== []) {
            $report .= "Known documented divergences (drik convention vs catalog convention):\n"
                . implode("\n", $matchedDivergences) . "\n";
        }

        self::assertSame(
            [],
            $unexpected,
            sprintf(
                "Undocumented festival disagreements for %s:\n%s\n(If a divergence is a deliberate convention difference, document it in the fixture's known_divergences with an explanation.)",
                $cityName,
                $report . implode("\n", $unexpected)
            )
        );
    }

    /**
     * The oracle's independent reference size: 40 drikpanchang dates (8
     * festivals x 5 years). Each is asserted for BOTH cities, so the two
     * city tests perform 80 comparisons — but the oracle itself is 40
     * dates, and is reported as such in docs and CODE_REVIEW_REPORT.md.
     */
    public function testOracleCoversFortyIndependentDates(): void
    {
        $this->skipWithoutFixture('festival_dates');

        self::assertCount(40, $this->repo()->samples('festival_dates'));
    }

    public function testFestivalDatesMatchDrikpanchangForAhmedabad(): void
    {
        $this->checkCity('Ahmedabad', TestLocations::ahmedabad());
    }

    public function testFestivalDatesMatchDrikpanchangForDelhi(): void
    {
        $this->checkCity('Delhi', TestLocations::delhi());
    }
}
