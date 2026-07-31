# Vittix Panchang

Dependency-free Hindu Panchang calculation engine written in pure PHP.

## Features

- Panchang day and moment calculations
- Janmarashi (Moon sign) calculation
- Tithi, Nakshatra, Yoga, Karana, and Vara support
- Lunar month and Adhika Masa calculations
- Festival and Muhurta helpers
- Pure PHP astronomy engine with no FFI, native binaries, or network dependency

## Requirements

- PHP 8.2 or newer
- Composer

## Install

```bash
composer install
```

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
