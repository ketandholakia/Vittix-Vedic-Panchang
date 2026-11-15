<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Support;

use Psr\SimpleCache\CacheInterface;

/**
 * Minimal in-memory PSR-16 cache for tests that records every key ever
 * written, so tests can assert on key shape and collisions.
 */
final class ArraySimpleCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $store = [];

    /** @var array<string, true> every key ever set (not cleared on clear()). */
    public array $seenKeys = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->store[$key] = $value;
        $this->seenKeys[$key] = true;

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
        return array_key_exists($key, $this->store);
    }

    /**
     * @return list<string> distinct keys ever written.
     */
    public function keys(): array
    {
        return array_keys($this->seenKeys);
    }
}
