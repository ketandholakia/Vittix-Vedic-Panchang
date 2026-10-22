<?php

declare(strict_types=1);

namespace Vittix\Panchang\Support;

/**
 * Minimal bounded in-memory LRU cache backed by an ordered PHP array.
 *
 * Entries live oldest-first; a hit moves the entry to the most-recently-used
 * end, and insertion beyond the capacity bound evicts the least recently used
 * entries. Everything is per-instance and never persisted.
 *
 * This is the single implementation shared by the astronomy engine's position
 * and ayanamsha caches and the PanchangService result cache.
 */
final class LruCache implements \Countable
{
    /** @var array<string, mixed> Ordered oldest-first, newest at the end. */
    private array $items = [];

    public function __construct(
        private readonly int $capacity,
    ) {
        if ($capacity < 1) {
            throw new \InvalidArgumentException('LRU cache capacity must be at least 1.');
        }
    }

    /**
     * Fetch an entry, moving it to the most-recently-used end.
     *
     * Returns null on a miss. Note that cached values in this codebase are
     * never null, so null is an unambiguous miss signal.
     */
    public function get(string $key): mixed
    {
        if (!array_key_exists($key, $this->items)) {
            return null;
        }

        $value = $this->items[$key];
        unset($this->items[$key]);
        $this->items[$key] = $value;

        return $value;
    }

    /**
     * Insert (or refresh) an entry at the most-recently-used end, evicting
     * the least recently used entries beyond the capacity bound.
     */
    public function set(string $key, mixed $value): void
    {
        unset($this->items[$key]);
        $this->items[$key] = $value;

        while (count($this->items) > $this->capacity) {
            $oldest = array_key_first($this->items);
            if ($oldest === null) {
                break;
            }
            unset($this->items[$oldest]);
        }
    }

    public function clear(): void
    {
        $this->items = [];
    }

    public function count(): int
    {
        return count($this->items);
    }
}
