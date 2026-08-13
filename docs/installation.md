# Installation

## Requirements

- **PHP 8.2 or newer** (supports PHP 8.2, 8.3, 8.4)
- **Composer** for dependency management
- **PSR-16 Simple Cache** implementation (optional, for caching astronomical calculations)

## Install via Composer

```bash
composer require vittix/panchang
```

## Development Setup

```bash
git clone https://github.com/ketandholakia/Vittix-Vedic-Panchang.git
cd Vittix-Vedic-Panchang
composer install
```

## Optional: Cache Configuration

For improved performance in production, configure a PSR-16 compatible cache:

```php
use Vittix\Panchang\Panchang;
use Symfony\Component\Cache\Adapter\FilesystemAdapter; // or RedisAdapter, etc.

$cache = new FilesystemAdapter('panchang', 0, '/path/to/cache/dir');
$panchang = Panchang::createDefault(\Vittix\Panchang\Enum\AyanamshaSystem::Lahiri, $cache);
```

## Supported Platforms

- **Plain PHP** applications (8.2+)
- **Laravel** (via service provider or manual instantiation)
- **Symfony** (via dependency injection)
- **Any PSR-4 compatible framework**

## No Native Extensions Required

Vittix Panchang runs entirely in pure PHP:
- No `sweph` (Swiss Ephemeris) extension needed
- No FFI or native libraries
- No network dependencies
- Works on shared hosting, serverless, and containerized environments

## Running Tests

```bash
composer test        # Run PHPUnit tests
composer analyse     # Run PHPStan static analysis
composer quality     # Run both tests and analysis