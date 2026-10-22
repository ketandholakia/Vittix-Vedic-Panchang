<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Panchanga;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use ReflectionClass;
use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * In-memory PSR-16 double that counts reads and writes, used to observe
 * whether the astronomy engine was consulted for a getForDate() call.
 * Note: the engine treats PSR-16 as a persistent layer it never clears,
 * so a re-consultation may re-fetch from it without new set() calls —
 * the has() counter is the reliable "engine was consulted" signal.
 */
final class CountingCache implements CacheInterface
{
    /** @var array<string, mixed> */
    public array $store = [];

    public int $setCount = 0;

    public int $hasCount = 0;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->store[$key] = $value;
        $this->setCount++;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->store[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->store = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        $this->hasCount++;

        return array_key_exists($key, $this->store);
    }
}

#[CoversClass(PanchangService::class)]
final class PanchangServiceCacheTest extends TestCase
{
    private GeoLocation $location;

    protected function setUp(): void
    {
        $this->location = new GeoLocation(latitude: 23.0225, longitude: 72.5714);
    }

    public function testRepeatCallsAreServedFromTheResultCache(): void
    {
        $spy = new CountingCache();
        $service = PanchangService::createDefault(cache: $spy);
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));

        $first = $service->getForDate($date, $this->location);
        $writesAfterFirst = $spy->setCount;
        $lookupsAfterFirst = $spy->hasCount;
        self::assertGreaterThan(0, $writesAfterFirst, 'engine should write positions to the PSR-16 cache on first computation');

        $second = $service->getForDate($date, $this->location);

        self::assertSame($first, $second, 'repeat call must be served from the result cache');
        self::assertSame($writesAfterFirst, $spy->setCount, 'no engine work (no new PSR-16 writes) on a result-cache hit');
        self::assertSame($lookupsAfterFirst, $spy->hasCount, 'the engine must not even be consulted on a result-cache hit');
    }

    public function testClearCacheForcesRecomputation(): void
    {
        $spy = new CountingCache();
        $service = PanchangService::createDefault(cache: $spy);
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));

        $service->getForDate($date, $this->location);
        $lookupsAfterFirst = $spy->hasCount;

        // Repeat: result-cache hit — the engine is not consulted at all.
        $service->getForDate($date, $this->location);
        self::assertSame($lookupsAfterFirst, $spy->hasCount);

        $service->clearCache();
        $service->getForDate($date, $this->location);

        self::assertGreaterThan(
            $lookupsAfterFirst,
            $spy->hasCount,
            'clearCache() must clear the result cache so the engine is consulted again'
        );
    }

    public function testDifferentInputsComputeSeparately(): void
    {
        $service = PanchangService::createDefault();
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));
        $delhi = new GeoLocation(latitude: 28.6139, longitude: 77.2090);

        $ahmedabad = $service->getForDate($date, $this->location);
        $purnimanta = $service->getForDate($date, $this->location, \Vittix\Panchang\Enum\MonthSystem::Purnimanta);
        $otherLocation = $service->getForDate($date, $delhi);

        self::assertNotSame($ahmedabad, $purnimanta);
        self::assertNotSame($ahmedabad, $otherLocation);
    }

    public function testResultCacheIsBoundedAtCapacity(): void
    {
        $service = PanchangService::createDefault();
        $reflection = new ReflectionClass($service);
        $resultCache = $reflection->getProperty('resultCache');
        /** @var \Vittix\Panchang\Support\LruCache $cache */
        $cache = $resultCache->getValue($service);

        for ($i = 0; $i < 600; $i++) {
            $cache->set("key:$i", new \stdClass());
        }

        self::assertSame(PanchangService::RESULT_CACHE_CAPACITY, count($cache));
        self::assertNull($cache->get('key:0'), 'oldest entry must be evicted');
        self::assertNotNull($cache->get('key:599'), 'newest entry must survive');
    }

    public function testLruHitRefreshesRecency(): void
    {
        $service = PanchangService::createDefault();
        $reflection = new ReflectionClass($service);
        $resultCache = $reflection->getProperty('resultCache');
        /** @var \Vittix\Panchang\Support\LruCache $cache */
        $cache = $resultCache->getValue($service);

        for ($i = 0; $i < 512; $i++) {
            $cache->set("key:$i", new \stdClass());
        }

        // Touch key:0 (least recently used) so it becomes most recently used.
        self::assertNotNull($cache->get('key:0'));

        // One more insertion must evict key:1 (now the oldest), not key:0.
        $cache->set('key:512', new \stdClass());

        self::assertNotNull($cache->get('key:0'), 'touched entry must survive');
        self::assertNull($cache->get('key:1'), 'untouched oldest entry must be evicted');
    }
}
