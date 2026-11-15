<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Festivals;

use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Calendar\LunarMonthCalculator;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Festivals\Festival;
use Vittix\Panchang\Festivals\FestivalEngine;
use Vittix\Panchang\Festivals\Rules\SunriseTithiRule;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\Tests\Support\MockAstronomyEngine;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * The per-year resolution cache of FestivalEngine is a bounded LRU
 * (capacity 64): scanning many (year, location) combinations must not grow
 * it without limit.
 */
final class FestivalEngineCacheBoundTest extends TestCase
{
    public function testResolutionCacheIsBoundedAt64Entries(): void
    {
        $mock = new MockAstronomyEngine();
        // Elongation 150° → tithi index 12: never matches (or neighbors)
        // the probe tithi (index 0), so every scored day returns 0.0
        // instantly and the year-scans stay fast.
        $mock->sunLongitude = 0.0;
        $mock->moonLongitude = 150.0;

        $engine = new FestivalEngine(
            $mock,
            new TithiCalculator($mock),
            new LunarMonthCalculator($mock),
        );
        $festival = new Festival(
            'probe',
            new SunriseTithiRule(LunarMonth::Chaitra, Tithi::ShuklaPratipada)
        );
        $engine->addFestival($festival);

        $tz = new DateTimeZone('UTC');
        for ($i = 0; $i < 200; $i++) {
            $location = new GeoLocation(
                latitude: (($i % 40) - 20) * 1.5,
                longitude: ($i - 100) * 1.7,
                elevationMeters: 0.0
            );
            $engine->resolve($festival, 2026, $location, $tz);
        }

        // The cache must have filled up AND been evicted back to capacity.
        self::assertSame(
            64,
            $engine->resolutionCacheCount(),
            'resolution cache must be bounded (LRU eviction at capacity 64)'
        );
    }
}
