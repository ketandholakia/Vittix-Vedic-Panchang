# Quick Start

## Installation

```bash
composer require vittix/panchang
```

## Basic Panchang Calculation

```php
<?php

declare(strict_types=1);

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\ValueObject\GeoLocation;

// Create the Panchang engine with default settings (Lahiri ayanamsha)
$panchang = Panchang::createDefault();

// Define date and location
$date = new DateTimeImmutable('2026-08-04', new DateTimeZone('Asia/Kolkata'));
$location = new GeoLocation(latitude: 28.6139, longitude: 77.2090); // New Delhi

// Get the complete daily Panchang (sunrise-to-sunrise day)
$day = $panchang->day($date, $location);

// Access the five limbs at sunrise
echo "Tithi: " . $day->tithiAtSunrise->nameEnglish() . PHP_EOL;
echo "Nakshatra: " . $day->nakshatraAtSunrise->nameEnglish() . " (Pada: " . $day->padaAtSunrise . ")" . PHP_EOL;
echo "Yoga: " . $day->yogaAtSunrise->nameEnglish() . PHP_EOL;
echo "Karana: " . $day->karanaAtSunrise->nameEnglish() . PHP_EOL;
echo "Vara: " . $day->vara->nameEnglish() . PHP_EOL;

// Solar events
echo "Sunrise: " . $day->solarEvents->sunrise?->format('H:i:s') . PHP_EOL;
echo "Sunset: " . $day->solarEvents->sunset?->format('H:i:s') . PHP_EOL;

// Transition times (when each limb ends)
echo "Tithi ends at: " . $day->tithiEndsAt?->format('H:i:s') . PHP_EOL;
echo "Nakshatra ends at: " . $day->nakshatraEndsAt?->format('H:i:s') . PHP_EOL;
```

## Kundli (Birth Chart) Generation

```php
$moment = new DateTimeImmutable('1990-01-01 06:30:00', new DateTimeZone('Asia/Kolkata'));
$kundli = $panchang->kundali($moment, $location);

// Ascendant (Lagna)
echo "Ascendant: " . $kundli->ascendant->rashi->nameEnglish() 
     . " at " . round($kundli->ascendant->degreeInSign, 2) . "°" . PHP_EOL;

// Planetary placements
$jupiter = $kundli->placements[\Vittix\Panchang\Enum\Body::Jupiter->value];
echo "Jupiter: House " . $jupiter->house 
     . ", " . $jupiter->rashi->nameEnglish() 
     . ", Dignity: " . ($jupiter->dignity?->value ?? 'N/A') . PHP_EOL;

// Aspects
if ($jupiter->aspectsHouses) {
    echo "Jupiter aspects houses: " . implode(', ', $jupiter->aspectsHouses) . PHP_EOL;
}
```

## JSON API (PanchangService)

For REST APIs and structured output:

```php
use Vittix\Panchang\Panchanga\PanchangService;

$service = PanchangService::createDefault();
$result = $service->getForDate($date, $location);

// Returns JSON-ready structured data
echo $result->toJson();
```

## Key Concepts

- **Panchang**: The main facade for object-oriented usage
- **PanchangService**: Optimized for JSON APIs with structured DTOs
- **GeoLocation**: Latitude, longitude, and optional elevation
- **DateTimeImmutable**: Always use with timezone (e.g., `Asia/Kolkata`)
- **Sunrise-to-sunrise day**: Hindu day starts at sunrise, not midnight