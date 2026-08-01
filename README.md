# Vittix Panchang

Dependency-free Hindu Panchang calculation engine written in pure PHP.

## Features

- Panchang day and moment calculations (Tithi, Nakshatra, Yoga, Karana, Vara)
- Janmarashi (Moon sign) and Lunar month calculations
- Festival and Muhurta helpers
- **Astrology Engine:** Generate complete Birth Charts (Kundali) using the Whole-Sign house system.
- **Divisional Charts (Shodashvargas):** Calculates all 16 traditional Parashari sub-charts (D1 to D60).
- **Planetary Strength (Shadbala):** Calculates Naisargika, Dik, Sthana, Ayana, and Chesta Balas.
- **Vimshottari Dasha:** Full 120-year planetary period sequences based on Nakshatra placement.
- **Classical Yogas:** Rules engine detects powerful planetary combinations like the Pancha Mahapurusha and Gajakesari Yogas.
- Localization files for Indian languages (Hindi, Gujarati, Sanskrit, Bengali, Marathi, Telugu, Kannada, Malayalam).
- Pure PHP astronomy engine with no FFI, native binaries, or network dependency.

## Requirements

- PHP 8.2 or newer
- Composer

## Install

This package is published on [Packagist](https://packagist.org/), so you can install it directly with Composer:

```bash
composer require vittix/panchang
```

If you are developing from a local checkout:

```bash
composer install
```

## Documentation & Usage

For comprehensive examples of initializing the engine, calculating Panchang data, generating birth charts, fetching Vargas, Dasha periods, and checking Shadbala, please see the **[Usage Guide](docs/USAGE.md)**.

## Basic Usage

```php
<?php

declare(strict_types=1);

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\ValueObject\GeoLocation;

$panchang = Panchang::createDefault();

$date = new DateTimeImmutable('2026-07-31', new DateTimeZone('Asia/Kolkata'));
$location = new GeoLocation(latitude: 23.0225, longitude: 72.5714);

$day = $panchang->day($date, $location);

echo $day->tithiAtSunrise->nameEnglish();
echo PHP_EOL;
echo $day->solarEvents->sunrise?->format('H:i:s');
```

## Public API

- `Panchang::createDefault()` creates an engine using the built-in pure PHP astronomy implementation.
- `Panchang::create(AstronomyEngineInterface $engine)` injects a custom astronomy engine.
- `Panchang::day(DateTimeImmutable $date, GeoLocation $location)` returns a sunrise-anchored daily Panchang.
- `Panchang::moment(DateTimeImmutable $moment, GeoLocation $location)` returns the Panchang state for a specific moment.
- `Panchang::janmarashi(DateTimeImmutable $moment)` returns the sidereal Moon sign at the given moment.
- `Panchang::kundali(DateTimeImmutable $moment, GeoLocation $location)` generates a full birth chart (Kundali).
- `Panchang::varga(DateTimeImmutable $moment, GeoLocation $location, Varga $varga)` projects a specific divisional chart (e.g. Navamsha D9).
- `Panchang::vimshottariDasha(DateTimeImmutable $moment)` computes the planetary Dasha periods.
- `Panchang::shadbala(DateTimeImmutable $moment, GeoLocation $location)` calculates six-fold planetary strength.
- `Panchang::yogas(DateTimeImmutable $moment, GeoLocation $location)` detects classical Yogas in the chart.

## Scripts

Defined in `composer.json`:

- `composer test` runs PHPUnit with testdox output
- `composer analyse` runs PHPStan at level 8
- `composer quality` runs analysis and tests

## Project Structure

- `src/` engine, calculations, enums, and value objects
- `tests/` unit tests, integration tests, fixtures, and test support
- `scripts/` helper scripts for localization extraction, snapshots, and verification

## Notes

- This repository is a library, so `composer.lock` is tracked.
- Generated regression snapshots live in `tests/fixtures/generated/` and are used by integration tests.
