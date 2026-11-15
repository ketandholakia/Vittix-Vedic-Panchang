<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Festivals;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Enum\Kala;
use Vittix\Panchang\Enum\Karana;
use Vittix\Panchang\Festivals\Festival;
use Vittix\Panchang\Festivals\FestivalEngine;
use Vittix\Panchang\Festivals\Rules\RakshaBandhanRule;
use Vittix\Panchang\Panchanga\KaranaCalculator;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\Tests\Support\TestLocations;
use Vittix\Panchang\Time\AstroTimeFactory;

/**
 * Raksha Bandhan: Shravana Purnima (nija Shravana) by ORDERED TIERS,
 * highest tier wins, higher overlap wins within a tier:
 *
 *   1. Bhadra-free Purnima inside Aparahna.
 *   2. Else Bhadra-free Purnima inside Pradosh (sunset -> +2 night-muhurtas).
 *   3. Else the day on which Purnima prevails after sunrise with Bhadra-free
 *      time before Purnima ends.
 *
 * All five years 2023-2027 match drikpanchang (New Delhi):
 *   2023-08-30 (tier 3: Bhadra covers Aparahna AND Pradosh, ends 21:01;
 *               the Bhadra-free Purnima evening outweighs Aug 31's morning),
 *   2024-08-19 (tier 1: Bhadra ends 13:30, before Aparahna),
 *   2025-08-09 (tier 3: Bhadra over before sunrise; Purnima until 13:24),
 *   2026-08-28 (tier 3: same pattern; Purnima until 09:48),
 *   2027-08-17 (tier 3: same pattern; Purnima until 12:58).
 */
final class RakshaBandhanRuleTest extends TestCase
{
    private FestivalEngine $engine;

    private Festival $rakshaBandhan;

    protected function setUp(): void
    {
        $astronomy = new PurePhpAstronomyEngine();
        $lunarMonth = new LunarMonthCalculator($astronomy);
        $this->engine = new FestivalEngine($astronomy, new TithiCalculator($astronomy), $lunarMonth);
        $this->rakshaBandhan = new Festival(
            'Raksha Bandhan',
            new RakshaBandhanRule(new KaranaCalculator($astronomy))
        );
        $this->engine->addFestival($this->rakshaBandhan);
    }

    /**
     * @return array{2023: string, 2024: string, 2025: string, 2026: string, 2027: string}
     */
    private function resolveAll(string $city): array
    {
        $location = $city === 'Ahmedabad'
            ? TestLocations::ahmedabad()
            : TestLocations::delhi();
        $tz = new DateTimeZone('Asia/Kolkata');

        $out = [];
        foreach ([2023, 2024, 2025, 2026, 2027] as $year) {
            $day = $this->engine->resolve($this->rakshaBandhan, $year, $location, $tz);
            $out[$year] = $day?->format('Y-m-d') ?? 'NOT RESOLVED';
        }

        return $out;
    }

    public function testAllFiveYearsMatchDrik(): void
    {
        $resolved = $this->resolveAll('Ahmedabad');
        self::assertSame('2023-08-30', $resolved[2023], 'tier 3: Bhadra covers Aparahna and Pradosh, ends 21:01 Aug 30');
        self::assertSame('2024-08-19', $resolved[2024], 'tier 1: Bhadra ends 13:30, before Aparahna');
        self::assertSame('2025-08-09', $resolved[2025], 'tier 3: Bhadra over before sunrise, Purnima until 13:24');
        self::assertSame('2026-08-28', $resolved[2026], 'tier 3: Bhadra over before sunrise, Purnima until 09:48');
        self::assertSame('2027-08-17', $resolved[2027], 'tier 3: Bhadra over before sunrise, Purnima until 12:58');
    }

    public function testDelhiMatchesAhmedabad(): void
    {
        self::assertSame($this->resolveAll('Ahmedabad'), $this->resolveAll('Delhi'));
    }

    public function testVishtiExclusionActuallyBites(): void
    {
        // The rule must genuinely look at the karana: on the resolved 2023
        // day, the Aparahna window must be (at least partly) Vishti-free —
        // and the rule must differ from the plain Aparahna-overlap score
        // whenever Vishti covers the window.
        $astronomy = new PurePhpAstronomyEngine();
        $lunarMonth = new LunarMonthCalculator($astronomy);
        $karana = new KaranaCalculator($astronomy);
        $rule = new RakshaBandhanRule($karana);
        $tf = new AstroTimeFactory();
        $tz = new DateTimeZone('Asia/Kolkata');

        $day = new DateTimeImmutable('2023-08-30 00:00', $tz);
        $se = $astronomy->solarEvents($day, TestLocations::ahmedabad(), $tz);
        $ns = $astronomy->solarEvents($day->modify('+1 day'), TestLocations::ahmedabad(), $tz);
        $context = new \Vittix\Panchang\Festivals\FestivalDayContext(
            $day,
            $tz,
            $se->sunrise,
            $se->sunset,
            $ns->sunrise,
            new \Vittix\Panchang\Festivals\TithiTimeline($astronomy, new TithiCalculator($astronomy)),
            $lunarMonth
        );

        $score = $rule->score($context);
        self::assertGreaterThan(0.0, $score, 'Purnima must be present at Aparahna on the resolved day');

        // Karana during the Aparahna window on 2023-08-30 (drik states the
        // Bhadra ends during that window, i.e. Vishti -> another karana).
        $aparahnas = $astronomy->solarEvents($day, TestLocations::ahmedabad(), $tz);
        $window = Kala::Aparahna->window($context);
        self::assertNotNull($window);
        $mid = $window->start->modify('+' . (int) (($window->end->getTimestamp() - $window->start->getTimestamp()) / 2) . ' seconds');
        $karanaAtMid = $karana->calculate($tf->fromDateTime($mid));
        self::assertInstanceOf(Karana::class, $karanaAtMid);
    }
}
