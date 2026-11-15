<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Astronomy\Engine;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Astronomy\Engine\PurePhpAstronomyEngine;
use Vittix\Panchang\Astronomy\Sidereal\KrishnamurtiAyanamsha;
use Vittix\Panchang\Astronomy\Sidereal\LahiriAyanamsha;
use Vittix\Panchang\Astronomy\Sidereal\RamanAyanamsha;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Tests\Support\ArraySimpleCache;
use Vittix\Panchang\Time\AstroTimeFactory;

final class PurePhpAstronomyEngineCacheKeyTest extends TestCase
{
    public function testAyanamshaKeysAreNamespacedBySystem(): void
    {
        $shared = new ArraySimpleCache();

        $lahiri = new PurePhpAstronomyEngine(new LahiriAyanamsha(), $shared);
        $raman = new PurePhpAstronomyEngine(new RamanAyanamsha(), $shared);
        $kp = new PurePhpAstronomyEngine(new KrishnamurtiAyanamsha(), $shared);

        $instant = (new AstroTimeFactory())->fromDateTime(
            new DateTimeImmutable('2026-11-15 12:00:00', new DateTimeZone('UTC'))
        );

        // Order matters: Raman writes into the shared cache before Lahiri
        // computes, so a system-less key would hand Lahiri's query Raman's
        // cached value.
        $ramanValue = $raman->ayanamsa($instant);
        $kpValue = $kp->ayanamsa($instant);
        $lahiriValue = $lahiri->ayanamsa($instant);

        self::assertEqualsWithDelta(24.229, $lahiriValue, 0.01);
        self::assertEqualsWithDelta(22.788, $ramanValue, 0.01);
        self::assertEqualsWithDelta(24.1466, $kpValue, 0.01);
        self::assertNotEquals($lahiriValue, $ramanValue);
        self::assertNotEquals($lahiriValue, $kpValue);

        // All three distinct values coexist in the one shared cache.
        self::assertCount(3, array_filter(
            $shared->keys(),
            static fn (string $k): bool => str_starts_with($k, 'vpanchang.ayan.')
        ));
    }

    public function testPositionReadsSharedCacheWithoutSystemCollision(): void
    {
        $shared = new ArraySimpleCache();

        $instant = (new AstroTimeFactory())->fromDateTime(
            new DateTimeImmutable('2026-11-15 12:00:00', new DateTimeZone('UTC'))
        );

        $first = new PurePhpAstronomyEngine(new LahiriAyanamsha(), $shared);
        $sunLongitude = $first->tropicalLongitude(Body::Sun, $instant);

        // A second engine with the same configuration must hit the shared
        // PSR-16 cache entry written by the first (memory cache is per engine).
        $second = new PurePhpAstronomyEngine(new LahiriAyanamsha(), $shared);
        self::assertEqualsWithDelta($sunLongitude, $second->tropicalLongitude(Body::Sun, $instant), 1e-9);
    }

    public function testCacheKeysContainNoPsr16ReservedCharacters(): void
    {
        $shared = new ArraySimpleCache();
        $engine = new PurePhpAstronomyEngine(new LahiriAyanamsha(), $shared);

        $instant = (new AstroTimeFactory())->fromDateTime(
            new DateTimeImmutable('2026-11-15 12:00:00', new DateTimeZone('UTC'))
        );

        foreach (Body::cases() as $body) {
            $engine->position($body, $instant);
        }
        $engine->ayanamsa($instant);

        $reserved = ['{', '}', '(', ')', '/', '\\', '@', ':'];
        foreach ($shared->keys() as $key) {
            foreach ($reserved as $char) {
                self::assertStringNotContainsString(
                    $char,
                    $key,
                    sprintf('Cache key %s contains reserved character %s', $key, $char)
                );
            }
            self::assertStringStartsWith('vpanchang.', $key);
        }

        self::assertArrayHasKey(
            'vpanchang.pos.sun.' . sprintf('%.8F', $instant->julianDayTt),
            $shared->seenKeys
        );
        self::assertArrayHasKey(
            'vpanchang.ayan.lahiri.' . sprintf('%.6F', $instant->julianDayTt),
            $shared->seenKeys
        );
    }
}
