<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Calendar\SamvatCalculator;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Festivals\Festival;
use Vittix\Panchang\Festivals\FestivalCatalog;
use Vittix\Panchang\Festivals\FestivalEngine;
use Vittix\Panchang\Festivals\NewYearResolver;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\Tests\Support\TestLocations;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * The Samvat new-year day must be derived with the SAME rule the festival
 * engine uses for Shukla Pratipada (greater-overlap fallback included),
 * via the shared {@see NewYearResolver} — and the year must change at
 * 00:00 local on the resolved civil day.
 *
 * Regression anchor: in 2026 the festival engine resolves Gudi Padwa to
 * Mar 19 (Pratipada never prevails at a sunrise), while the old
 * "first sunrise at or after the new moon" rule put the new year on
 * Mar 20 — so Mar 19 was the festival but still carried the old year.
 */
final class SamvatNewYearAlignmentTest extends TestCase
{
    private const TZ = 'Asia/Kolkata';

    private SamvatCalculator $samvat;

    private NewYearResolver $resolver;

    private GeoLocation $location;

    private Festival $gudiPadwa;

    private FestivalEngine $festivalEngine;

    protected function setUp(): void
    {
        $astronomy = new PurePhpAstronomyEngine();
        $lunarMonth = new LunarMonthCalculator($astronomy);
        $this->samvat = new SamvatCalculator($lunarMonth, $astronomy);
        $this->resolver = new NewYearResolver($astronomy, new TithiCalculator($astronomy), $lunarMonth);
        $this->location = TestLocations::ahmedabad();

        foreach (FestivalCatalog::getDefaultFestivals() as $festival) {
            if ($festival->name === 'Gudi Padwa / Ugadi') {
                $this->gudiPadwa = $festival;
            }
        }
        $this->festivalEngine = new FestivalEngine($astronomy, new TithiCalculator($astronomy), $lunarMonth);
        $this->festivalEngine->addFestival($this->gudiPadwa);
    }

    private function vs(string $dateTime): int
    {
        return $this->samvat->vikramSamvatExact(
            (new \Vittix\Panchang\Time\AstroTimeFactory())->fromDateTime(
                new DateTimeImmutable($dateTime, new DateTimeZone(self::TZ))
            ),
            $this->location,
            new DateTimeZone(self::TZ)
        );
    }

    public function testVikramFlipsOnTheFestivalResolvedDay2026(): void
    {
        // Gudi Padwa 2026 = Mar 19 (greater-overlap fallback). The year must
        // change at 00:00 local on Mar 19, not on Mar 20.
        self::assertSame(2082, $this->vs('2026-03-18 12:00'));
        self::assertSame(2083, $this->vs('2026-03-19 00:00'), 'year changes at 00:00 local on the new-year day');
        self::assertSame(2083, $this->vs('2026-03-19 12:00'));
        self::assertSame(2083, $this->vs('2026-03-20 12:00'));
    }

    /**
     * 2023–2027: the resolver's Chaitra day must equal the festival
     * engine's Gudi Padwa resolution, and the Vikram year must flip at
     * 00:00 local on that day (Y-1+57 before, Y+57 from it).
     */
    public function testChaitraNewYearMatchesGudiPadwa2023To2027(): void
    {
        foreach ([2023, 2024, 2025, 2026, 2027] as $year) {
            $festivalDay = $this->festivalEngine->resolve($this->gudiPadwa, $year, $this->location, new DateTimeZone(self::TZ));
            self::assertNotNull($festivalDay, "Gudi Padwa must resolve for $year");

            $resolverDay = $this->resolver->resolve(LunarMonth::Chaitra, $year, $this->location, new DateTimeZone(self::TZ));
            self::assertNotNull($resolverDay);
            self::assertSame(
                $festivalDay->format('Y-m-d'),
                $resolverDay->format('Y-m-d'),
                "Chaitra new year $year must match the Gudi Padwa festival resolution"
            );

            $dayBefore = $resolverDay->modify('-1 day')->format('Y-m-d');
            self::assertSame(
                $year - 1 + 57,
                $this->vs($dayBefore . ' 23:59'),
                "Vikram before Chaitra new year $year"
            );
            self::assertSame(
                $year + 57,
                $this->vs($resolverDay->format('Y-m-d') . ' 00:00'),
                "Vikram from 00:00 local on Chaitra new year $year"
            );
        }
    }

    /**
     * 2023–2027: the Kartika new year (Gujarati) must flip the Gujarati
     * year at 00:00 local on the resolver's day.
     */
    public function testKartikaNewYearDrivesGujaratiSamvat2023To2027(): void
    {
        foreach ([2023, 2024, 2025, 2026, 2027] as $year) {
            $day = $this->resolver->resolve(LunarMonth::Kartika, $year, $this->location, new DateTimeZone(self::TZ));
            self::assertNotNull($day, "Kartika new year must resolve for $year");

            $gujaratiOn = $this->samvat->gujaratiSamvatExact(
                (new \Vittix\Panchang\Time\AstroTimeFactory())->fromDateTime(
                    new DateTimeImmutable($day->format('Y-m-d') . ' 00:00', new DateTimeZone(self::TZ))
                ),
                $this->location,
                new DateTimeZone(self::TZ)
            );
            $gujaratiBefore = $this->samvat->gujaratiSamvatExact(
                (new \Vittix\Panchang\Time\AstroTimeFactory())->fromDateTime(
                    new DateTimeImmutable($day->modify('-1 day')->format('Y-m-d') . ' 23:59', new DateTimeZone(self::TZ))
                ),
                $this->location,
                new DateTimeZone(self::TZ)
            );

            self::assertSame($year + 57, $gujaratiOn, "Gujarati from 00:00 local on Kartika new year $year");
            self::assertSame($year - 1 + 57, $gujaratiBefore, "Gujarati before Kartika new year $year");
        }
    }
}
