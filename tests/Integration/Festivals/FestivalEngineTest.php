<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Festivals;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Festivals\Festival;
use Vittix\Panchang\Festivals\FestivalEngine;
use Vittix\Panchang\Festivals\Rules\SunriseTithiRule;
use Vittix\Panchang\Panchanga\KaranaCalculator;
use Vittix\Panchang\Panchanga\NakshatraCalculator;
use Vittix\Panchang\Panchanga\PanchangaCalculator;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\Panchanga\TransitionFinder;
use Vittix\Panchang\Panchanga\VaraCalculator;
use Vittix\Panchang\Panchanga\YogaCalculator;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Calendar\AdhikaMasaCalculator;

final class FestivalEngineTest extends TestCase
{
    public function testSunriseTithiFestival(): void
    {
        $engine = new PurePhpAstronomyEngine();
        $finder = new TransitionFinder($engine);
        
        $tithiCalc = new TithiCalculator($engine);
        $nakshatraCalc = new NakshatraCalculator($engine);
        $yogaCalc = new YogaCalculator($engine);
        $karanaCalc = new KaranaCalculator($engine);
        $varaCalc = new VaraCalculator();
        $lunarMonthCalc = new LunarMonthCalculator($engine);
        $adhikaCalc = new AdhikaMasaCalculator($engine, $lunarMonthCalc);
        $panchangaCalc = new PanchangaCalculator(
            $engine,
            $tithiCalc,
            $nakshatraCalc,
            $yogaCalc,
            $karanaCalc,
            $varaCalc,
            $finder,
            $lunarMonthCalc,
            $adhikaCalc
        );

        $festivalEngine = new FestivalEngine($panchangaCalc);

        // Add Ganesha Chaturthi (Bhadrapada, Shukla Chaturthi)
        $ganeshaChaturthi = new Festival(
            'Ganesha Chaturthi',
            new SunriseTithiRule(LunarMonth::Bhadrapada, Tithi::ShuklaD4)
        );
        $festivalEngine->addFestival($ganeshaChaturthi);

        // Date where we know it's NOT Bhadrapada Shukla Chaturthi
        $location = new \Vittix\Panchang\ValueObject\GeoLocation(19.0760, 72.8777); // Mumbai
        $wrongDate = new DateTimeImmutable('2026-07-31 06:10:00', new DateTimeZone('Asia/Kolkata'));
        $festivals = $festivalEngine->getFestivalsForDate($wrongDate, $location);
        self::assertEmpty($festivals);

        // Date where it IS Bhadrapada Shukla Chaturthi
        // In 2026, Ganesha Chaturthi is roughly around September 15.
        $correctDate = new DateTimeImmutable('2026-09-15 06:10:00', new DateTimeZone('Asia/Kolkata'));
        $festivals = $festivalEngine->getFestivalsForDate($correctDate, $location);
        self::assertContains('Ganesha Chaturthi', $festivals);
    }
}
