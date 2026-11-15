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
use Vittix\Panchang\Festivals\FestivalEngine;
use Vittix\Panchang\Festivals\Rules\ViddhaTithiRule;
use Vittix\Panchang\Panchanga\TithiCalculator;

final class FestivalEngineTest extends TestCase
{
    public function testMadhyahnaViddhaFestival(): void
    {
        $engine = new PurePhpAstronomyEngine();

        $tithiCalc = new TithiCalculator($engine);
        $lunarMonthCalc = new LunarMonthCalculator($engine);
        $festivalEngine = new FestivalEngine($engine, $tithiCalc, $lunarMonthCalc);

        // Ganesha Chaturthi: Bhadrapada Shukla Chaturthi at Madhyahna — the
        // drik convention. In 2026 Chaturthi runs 07:06 Sep 14 → 07:44
        // Sep 15 (no sunrise match on Sep 14), and the Madhyahna overlap
        // resolves Sep 14, not Sep 15.
        $ganeshaChaturthi = new Festival(
            'Ganesha Chaturthi',
            new ViddhaTithiRule(LunarMonth::Bhadrapada, Tithi::ShuklaD4, Kala::Madhyahna)
        );
        $festivalEngine->addFestival($ganeshaChaturthi);

        // Date where we know it's NOT Bhadrapada Shukla Chaturthi
        $location = new \Vittix\Panchang\ValueObject\GeoLocation(19.0760, 72.8777); // Mumbai
        $wrongDate = new DateTimeImmutable('2026-07-31 06:10:00', new DateTimeZone('Asia/Kolkata'));
        $festivals = $festivalEngine->getFestivalsForDate($wrongDate, $location);
        self::assertEmpty($festivals);

        // Ganesha Chaturthi 2026 resolves to exactly one date, Sep 14.
        $fired = [];
        for ($d = 12; $d <= 17; $d++) {
            $date = new DateTimeImmutable(sprintf('2026-09-%02d 06:10:00', $d), new DateTimeZone('Asia/Kolkata'));
            if (in_array('Ganesha Chaturthi', $festivalEngine->getFestivalsForDate($date, $location), true)) {
                $fired[] = $date->format('Y-m-d');
            }
        }
        self::assertSame(['2026-09-14'], $fired);
    }
}
