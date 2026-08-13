<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\Astronomy\Engine\EngineFactory;
use Vittix\Panchang\Enum\AyanamshaSystem;

// Simple in-memory array cache
class ArrayCache implements \Psr\SimpleCache\CacheInterface {
    private array $data = [];

    public function get(string $key, mixed $default = null): mixed {
        return $this->data[$key] ?? $default;
    }
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool {
        $this->data[$key] = $value;
        return true;
    }
    public function delete(string $key): bool {
        unset($this->data[$key]);
        return true;
    }
    public function clear(): bool {
        $this->data = [];
        return true;
    }
    public function getMultiple(iterable $keys, mixed $default = null): iterable {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }
    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }
    public function deleteMultiple(iterable $keys): bool {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }
    public function has(string $key): bool {
        return array_key_exists($key, $this->data);
    }
}

$cache = new ArrayCache();
$panchang = PanchangService::createDefault(AyanamshaSystem::Lahiri, $cache);

$startDate = new DateTimeImmutable('2024-01-01', new DateTimeZone('Asia/Kolkata'));
$location = new GeoLocation(28.6139, 77.2090); // New Delhi

$startMemory = memory_get_usage();
$startTime = microtime(true);

for ($i = 0; $i < 365; $i++) {
    $date = $startDate->modify("+$i days");
    $day = $panchang->getForDate($date, $location);
}

$endTime = microtime(true);
$endMemory = memory_get_usage();

$timeTaken = $endTime - $startTime;
$memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;

printf("Time taken: %.2f seconds\n", $timeTaken);
printf("Memory used: %.2f MB\n", $memoryUsed);
