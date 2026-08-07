# Vittix Vedic Panchang & Astrology Engine

Accurate Vedic Panchang, Kundli, Astrology, Muhurta, and Festival calculations for PHP & Laravel.

Official demo: https://hindutithi.in/

## Features

### Panchang
- Daily Panchang and moment calculations
- Tithi, Nakshatra, Yoga, Karana, Vara
- Sunrise, sunset, and Muhurta helpers
- Lunar month, Janmarashi, and festival support
- Hindu calendar and Adhika Masa calculations

### Astrology
- Birth chart (Kundli) generation
- Ascendant (Lagna) and house placement
- Planetary positions and Rashis
- Divisional charts (Vargas)
- Vimshottari Dasha and yogas
- Shadbala planetary strength

## Installation

Install with Composer:

```bash
composer require vittix/panchang
```

## Quick Start

```php
<?php

declare(strict_types=1);

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\ValueObject\GeoLocation;

$panchang = Panchang::createDefault();
$date = new DateTimeImmutable('2026-08-04', new DateTimeZone('Asia/Kolkata'));
$location = new GeoLocation(latitude: 28.6139, longitude: 77.2090);

$day = $panchang->day($date, $location);

echo $day->tithiAtSunrise->nameEnglish();
```

## Documentation

- [Installation](docs/installation.md)
- [Quick Start](docs/quick-start.md)
- [Panchang](docs/panchang.md)
- [Kundli](docs/kundli.md)
- [Astrology](docs/astrology.md)
- [API](docs/api.md)
- [JSON API Schema](docs/json-api.md)
- [Accuracy](docs/accuracy.md)
- [Examples](docs/examples/README.md)

## Public API

### JSON / REST APIs
- `PanchangService::createDefault()`
- `PanchangService::getForDate(DateTimeImmutable $date, GeoLocation $location)`
- `PanchangService::getAstronomyReport(DateTimeImmutable $date, GeoLocation $location)`

### Object-Oriented APIs
- `Panchang::createDefault()`
- `Panchang::create(AstronomyEngineInterface $engine)`
- `Panchang::day(DateTimeImmutable $date, GeoLocation $location)`
- `Panchang::moment(DateTimeImmutable $moment, GeoLocation $location)`
- `Panchang::janmarashi(DateTimeImmutable $moment)`
- `Panchang::kundali(DateTimeImmutable $moment, GeoLocation $location)`
- `Panchang::varga(DateTimeImmutable $moment, GeoLocation $location, Varga $varga)`
- `Panchang::vimshottariDasha(DateTimeImmutable $moment)`
- `Panchang::shadbala(DateTimeImmutable $moment, GeoLocation $location)`
- `Panchang::yogas(DateTimeImmutable $moment, GeoLocation $location)`
- `Panchang::ascendant(DateTimeImmutable $moment, GeoLocation $location)`

## Scripts

- `composer test`
- `composer analyse`
- `composer quality`

## Project Structure

- `src/` engine, calculations, enums, and value objects
- `tests/` unit and integration tests
- `docs/` user guides and examples
- `scripts/` repository tooling

## License

This project is licensed under the MIT License.
