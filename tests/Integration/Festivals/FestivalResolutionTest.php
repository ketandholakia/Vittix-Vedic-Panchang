<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Festivals;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Enum\Kala;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Festivals\Festival;
use Vittix\Panchang\Festivals\FestivalCatalog;
use Vittix\Panchang\Festivals\FestivalEngine;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\Tests\Support\TestLocations;

/**
 * Festival engine resolution guarantees.
 *
 * Every festival in the default catalog must resolve to exactly one civil
 * date per year (2023–2026). This catches the 2023 Adhika double-fires
 * (Raksha Bandhan Aug 1 + Aug 31, Janmashtami Aug 8 + Sep 6) and the 2026
 * missed festivals (Gudi Padwa Mar 19, Janmashtami), and pins the dates of
 * the one-day-off cases verified against drikpanchang.com:
 *
 *   • Gudi Padwa 2026-03-19 (Pratipada never prevails at a sunrise).
 *   • Diwali: 2023-11-12, 2025-10-20, 2026-11-08 (Amavasya at Pradosh).
 *   • Akshaya Tritiya 2026-04-19 (Tritiya at Madhyahna).
 *   • Janmashtami 2026-09-04 (drikpanchang: Ashtami ends 00:13 Sep 5, the
 *     Nishita muhurta of the night beginning Sep 4 starts ~00:15 — no
 *     overlap; the nearest-window fallback resolves Sep 4).
 */
final class FestivalResolutionTest extends TestCase
{
    private FestivalEngine $engine;

    private DateTimeZone $timezone;

    protected function setUp(): void
    {
        $astronomy = new PurePhpAstronomyEngine();
        $lunarMonth = new LunarMonthCalculator($astronomy);

        $this->engine = new FestivalEngine(
            $astronomy,
            new TithiCalculator($astronomy),
            $lunarMonth,
        );
        foreach (FestivalCatalog::getDefaultFestivals() as $festival) {
            $this->engine->addFestival($festival);
        }

        $this->timezone = new DateTimeZone('Asia/Kolkata');
    }

    /**
     * @return array<string, ?DateTimeImmutable> festival name → resolved date
     */
    private function resolutions(int $year): array
    {
        $result = [];
        foreach (FestivalCatalog::getDefaultFestivals() as $festival) {
            $result[$festival->name] = $this->engine->resolve($festival, $year, TestLocations::ahmedabad(), $this->timezone);
        }

        return $result;
    }

    public function testEveryFestivalResolvesToExactlyOneDatePerYear(): void
    {
        foreach ([2023, 2024, 2025, 2026] as $year) {
            $resolutions = $this->resolutions($year);

            foreach (FestivalCatalog::getDefaultFestivals() as $festival) {
                $resolved = $resolutions[$festival->name] ?? null;
                self::assertNotNull(
                    $resolved,
                    sprintf('%s must resolve for %d', $festival->name, $year)
                );

                // Exactly one firing date: scan the whole year through the
                // public per-date API.
                $fired = [];
                $day = new DateTimeImmutable("$year-01-01 00:00:00", $this->timezone);
                $end = new DateTimeImmutable(($year + 1) . "-01-01 00:00:00", $this->timezone);
                while ($day < $end) {
                    if (in_array($festival->name, $this->engine->getFestivalsForDate($day, TestLocations::ahmedabad()), true)) {
                        $fired[] = $day->format('Y-m-d');
                    }
                    $day = $day->modify('+1 day');
                }

                self::assertCount(
                    1,
                    $fired,
                    sprintf('%s must fire on exactly one date in %d, got: %s', $festival->name, $year, implode(', ', $fired))
                );
                self::assertSame(
                    $resolved->format('Y-m-d'),
                    $fired[0],
                    sprintf('%s resolution must match the per-date API in %d', $festival->name, $year)
                );
            }
        }
    }

    public function testDiwaliFollowsAmavasyaAtPradosh(): void
    {
        $expectations = [
            2023 => '2023-11-12',
            2025 => '2025-10-20',
            2026 => '2026-11-08',
        ];
        foreach ($expectations as $year => $expected) {
            $resolved = $this->resolutions($year)['Diwali'] ?? null;
            self::assertNotNull($resolved);
            self::assertSame($expected, $resolved->format('Y-m-d'), "Diwali $year");
        }
    }

    public function testGudiPadwa2026(): void
    {
        // Pratipada starts 06:54 (after the 06:45 sunrise) and ends before
        // the next sunrise — it never prevails at a sunrise. The
        // greater-overlap fallback must still resolve Mar 19.
        $resolved = $this->resolutions(2026)['Gudi Padwa / Ugadi'] ?? null;
        self::assertNotNull($resolved);
        self::assertSame('2026-03-19', $resolved->format('Y-m-d'));
    }

    public function testJanmashtami2026(): void
    {
        // drikpanchang (New Delhi): Janmashtami = Sep 4; Ashtami begins
        // 02:25 Sep 4 and ends 00:13 Sep 5, while the Nishita muhurta of the
        // night beginning Sep 4 starts ~00:15 — a 2-minute gap with no
        // overlap. The nearest-window fallback must still resolve Sep 4.
        $resolved = $this->resolutions(2026)['Krishna Janmashtami'] ?? null;
        self::assertNotNull($resolved);
        self::assertSame('2026-09-04', $resolved->format('Y-m-d'));
    }

    public function testAkshayaTritiya2026(): void
    {
        // Tritiya prevails at Madhyahna on Apr 19 and ends 07:27 on Apr 20,
        // before that day's Madhyahna — Apr 19, not Apr 20.
        $resolved = $this->resolutions(2026)['Akshaya Tritiya'] ?? null;
        self::assertNotNull($resolved);
        self::assertSame('2026-04-19', $resolved->format('Y-m-d'));
    }

    public function testNoAdhikaDoubleFire2023(): void
    {
        // Regression for the Adhika Shravana double-fires.
        $resolutions = $this->resolutions(2023);
        self::assertNotNull($resolutions['Raksha Bandhan']);
        self::assertNotNull($resolutions['Krishna Janmashtami']);
        // Neither may fire during the Adhika lunation (before Aug 17 2023).
        self::assertGreaterThan('2023-08-16', $resolutions['Raksha Bandhan']->format('Y-m-d'));
        self::assertGreaterThan('2023-08-16', $resolutions['Krishna Janmashtami']->format('Y-m-d'));
    }

    public function testKalaWindowsAreWellFormed(): void
    {
        $astronomy = new PurePhpAstronomyEngine();
        $lunarMonth = new LunarMonthCalculator($astronomy);
        $timeline = new \Vittix\Panchang\Festivals\TithiTimeline($astronomy, new TithiCalculator($astronomy));

        $day = new DateTimeImmutable('2026-06-15 00:00:00', $this->timezone);
        $sunrise = $astronomy->solarEvents($day, TestLocations::ahmedabad(), $this->timezone)->sunrise;
        $sunset = $astronomy->solarEvents($day, TestLocations::ahmedabad(), $this->timezone)->sunset;
        $nextSunrise = $astronomy->solarEvents($day->modify('+1 day'), TestLocations::ahmedabad(), $this->timezone)->sunrise;
        self::assertNotNull($sunrise);
        self::assertNotNull($sunset);
        self::assertNotNull($nextSunrise);

        $context = new \Vittix\Panchang\Festivals\FestivalDayContext(
            $day,
            $this->timezone,
            $sunrise,
            $sunset,
            $nextSunrise,
            $timeline,
            $lunarMonth,
        );

        foreach (Kala::cases() as $kala) {
            $window = $kala->window($context);
            self::assertNotNull($window, "{$kala->value} window must exist at Ahmedabad");
            self::assertGreaterThan($window->start->getTimestamp(), $window->end->getTimestamp(), "{$kala->value} window must have positive length");
        }

        // Nishita must sit inside the night owned by this day and contain
        // the night's midpoint (the 8th of 15 muhurtas).
        $nishita = Kala::Nishita->window($context);
        self::assertNotNull($nishita);
        self::assertGreaterThanOrEqual($sunset->getTimestamp(), $nishita->start->getTimestamp());
        self::assertLessThanOrEqual($nextSunrise->getTimestamp(), $nishita->end->getTimestamp());
        $nightMid = intdiv($sunset->getTimestamp() + $nextSunrise->getTimestamp(), 2);
        self::assertGreaterThanOrEqual($nishita->start->getTimestamp(), $nightMid);
        self::assertLessThanOrEqual($nishita->end->getTimestamp(), $nightMid);
    }
}
