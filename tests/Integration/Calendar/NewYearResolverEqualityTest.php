<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Festivals\Festival;
use Vittix\Panchang\Festivals\FestivalCatalog;
use Vittix\Panchang\Festivals\FestivalEngine;
use Vittix\Panchang\Festivals\NewYearResolver;
use Vittix\Panchang\Panchanga\TithiCalculator;

/**
 * The fast lunation-walk new-year resolution must be identical to the
 * whole-year festival-scan result for every year and city.
 */
#[Group('slow')]
final class NewYearResolverEqualityTest extends TestCase
{
    private const CITIES = [
        'Ahmedabad' => [23.0225, 72.5714],
        'Delhi' => [28.6139, 77.2090],
        'New York' => [40.7128, -74.0060],
        'Sydney' => [-33.8688, 151.2093],
    ];

    private const YEARS = [2020, 2021, 2022, 2023, 2024, 2025, 2026, 2027, 2028, 2029, 2030, 2031, 2032, 2033, 2034, 2035];

    public function testFastResolutionEqualsWholeYearResolution(): void
    {
        $astronomy = new PurePhpAstronomyEngine();
        $lunarMonth = new LunarMonthCalculator($astronomy);
        $resolver = new NewYearResolver($astronomy, new TithiCalculator($astronomy), $lunarMonth);

        $gudiPadwa = $this->festivalNamed('Gudi Padwa / Ugadi');
        $engine = new FestivalEngine($astronomy, new TithiCalculator($astronomy), $lunarMonth);
        $engine->addFestival($gudiPadwa);

        foreach (self::CITIES as $cityName => [$lat, $lon]) {
            $location = new \Vittix\Panchang\ValueObject\GeoLocation($lat, $lon, 0.0);
            $tz = new DateTimeZone($this->tzFor($cityName));

            foreach (self::YEARS as $year) {
                $wholeYear = $engine->resolve($gudiPadwa, $year, $location, $tz);
                $fast = $resolver->newYearDay(LunarMonth::Chaitra, $year, $location, $tz);

                self::assertSame(
                    $wholeYear?->format('Y-m-d'),
                    $fast?->format('Y-m-d'),
                    sprintf('Chaitra new year %d in %s', $year, $cityName)
                );
            }
        }
    }

    public function testFastKartikaResolutionEqualsWholeYearResolution(): void
    {
        $astronomy = new PurePhpAstronomyEngine();
        $lunarMonth = new LunarMonthCalculator($astronomy);
        $resolver = new NewYearResolver($astronomy, new TithiCalculator($astronomy), $lunarMonth);

        $bestuVaras = new Festival(
            'lunar-new-year-kartika',
            new \Vittix\Panchang\Festivals\Rules\SunriseTithiRule(LunarMonth::Kartika, \Vittix\Panchang\Enum\Tithi::ShuklaPratipada)
        );
        $engine = new FestivalEngine($astronomy, new TithiCalculator($astronomy), $lunarMonth);
        $engine->addFestival($bestuVaras);

        foreach (self::CITIES as $cityName => [$lat, $lon]) {
            $location = new \Vittix\Panchang\ValueObject\GeoLocation($lat, $lon, 0.0);
            $tz = new DateTimeZone($this->tzFor($cityName));

            foreach (self::YEARS as $year) {
                $wholeYear = $engine->resolve($bestuVaras, $year, $location, $tz);
                $fast = $resolver->newYearDay(LunarMonth::Kartika, $year, $location, $tz);

                self::assertSame(
                    $wholeYear?->format('Y-m-d'),
                    $fast?->format('Y-m-d'),
                    sprintf('Kartika new year %d in %s', $year, $cityName)
                );
            }
        }
    }

    private function festivalNamed(string $name): Festival
    {
        foreach (FestivalCatalog::getDefaultFestivals() as $festival) {
            if ($festival->name === $name) {
                return $festival;
            }
        }

        self::fail("Festival '$name' missing from the catalog");
    }

    private function tzFor(string $city): string
    {
        return [
            'Ahmedabad' => 'Asia/Kolkata',
            'Delhi' => 'Asia/Kolkata',
            'New York' => 'America/New_York',
            'Sydney' => 'Australia/Sydney',
        ][$city];
    }
}
