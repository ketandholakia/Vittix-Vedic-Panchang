<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Panchanga;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Batch-generation memory and cache-effectiveness regression tests.
 *
 * Background: the per-call clearCache() added in v2.4.0 was a blunt fix for
 * unbounded engine cache growth in long-running batch loops. The bounded LRU
 * caches (engine + service result cache) bound memory structurally while
 * keeping cross-call memoization, so these tests assert BOTH properties:
 * memory stays flat across large batches, and repeated dates are faster.
 */
#[CoversClass(PanchangService::class)]
final class BatchMemoryTest extends TestCase
{
    private const BATCH_DAYS = 400;

    private const MEMORY_BUDGET_BYTES = 100 * 1024 * 1024; // 100 MB

    private GeoLocation $location;

    private DateTimeZone $timezone;

    protected function setUp(): void
    {
        $this->location = new GeoLocation(latitude: 23.0225, longitude: 72.5714);
        $this->timezone = new DateTimeZone('Asia/Kolkata');
    }

    public function testBatchMemoryStaysBoundedAcrossConsecutiveDays(): void
    {
        $service = PanchangService::createDefault();
        $start = new DateTimeImmutable('2026-01-01', $this->timezone);

        $endOfPass1 = $this->runBatchPass($service, $start);
        $endOfPass2 = $this->runBatchPass($service, $start);

        self::assertLessThan(
            self::MEMORY_BUDGET_BYTES,
            memory_get_peak_usage(true),
            'peak memory for a 400-day batch must stay under 100 MB'
        );

        self::assertLessThan(
            2 * 1024 * 1024,
            abs($endOfPass2 - $endOfPass1),
            'memory after the second pass must match the first — LRU eviction must keep usage flat'
        );
    }

    public function testSecondPassOverSameDaysIsFaster(): void
    {
        $service = PanchangService::createDefault();
        $dates = $this->batchDates(new DateTimeImmutable('2026-01-01', $this->timezone), 30);

        $start = microtime(true);
        foreach ($dates as $date) {
            $service->getForDate($date, $this->location);
        }
        $coldDuration = microtime(true) - $start;

        $start = microtime(true);
        foreach ($dates as $date) {
            $service->getForDate($date, $this->location);
        }
        $warmDuration = microtime(true) - $start;

        self::assertGreaterThan(0.05, $coldDuration, 'cold pass should do real work (sanity check)');
        self::assertLessThan(
            $coldDuration * 0.5,
            $warmDuration,
            'warm pass over the same 30 days must be served from the result cache (≥2x faster)'
        );
    }

    public function testClearCacheStillReclaimsMemory(): void
    {
        $service = PanchangService::createDefault();
        $dates = $this->batchDates(new DateTimeImmutable('2026-01-01', $this->timezone), 60);

        foreach ($dates as $date) {
            $service->getForDate($date, $this->location);
        }

        $before = memory_get_usage();
        $service->clearCache();
        $after = memory_get_usage();

        self::assertLessThan($before, $after, 'clearCache() must release the result cache');
    }

    /**
     * One full batch pass; returns process memory usage at the end.
     */
    private function runBatchPass(PanchangService $service, DateTimeImmutable $start): int
    {
        foreach ($this->batchDates($start, self::BATCH_DAYS) as $date) {
            $service->getForDate($date, $this->location);
        }

        return memory_get_usage();
    }

    /**
     * @return list<DateTimeImmutable>
     */
    private function batchDates(DateTimeImmutable $start, int $days): array
    {
        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = $start->modify("+{$i} days");
        }

        return $dates;
    }
}
