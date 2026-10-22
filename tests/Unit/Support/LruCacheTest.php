<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Support;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Support\LruCache;

#[CoversClass(LruCache::class)]
final class LruCacheTest extends TestCase
{
    public function testGetReturnsNullOnMiss(): void
    {
        $cache = new LruCache(2);

        self::assertNull($cache->get('missing'));
    }

    public function testSetThenGetReturnsValue(): void
    {
        $cache = new LruCache(2);
        $value = new \stdClass();
        $cache->set('a', $value);

        self::assertSame($value, $cache->get('a'));
    }

    public function testEvictsLeastRecentlyUsedBeyondCapacity(): void
    {
        $cache = new LruCache(2);
        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->set('c', 3);

        self::assertNull($cache->get('a'), 'oldest entry must be evicted');
        self::assertSame(2, $cache->get('b'));
        self::assertSame(3, $cache->get('c'));
        self::assertCount(2, $cache);
    }

    public function testHitRefreshesRecency(): void
    {
        $cache = new LruCache(2);
        $cache->set('a', 1);
        $cache->set('b', 2);

        // Touch 'a' so it becomes most recently used.
        self::assertSame(1, $cache->get('a'));

        $cache->set('c', 3);

        self::assertNotNull($cache->get('a'), 'touched entry must survive');
        self::assertNull($cache->get('b'), 'untouched oldest entry must be evicted');
        self::assertSame(3, $cache->get('c'));
    }

    public function testClearEmptiesTheCache(): void
    {
        $cache = new LruCache(2);
        $cache->set('a', 1);
        $cache->clear();

        self::assertSame(0, count($cache));
        self::assertNull($cache->get('a'));
    }

    public function testCapacityBelowOneIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new LruCache(0);
    }
}
