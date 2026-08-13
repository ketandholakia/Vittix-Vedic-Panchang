# Usage Guide

Complete guide to using Vittix Vedic Panchang in your PHP applications.

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Daily Panchang](#daily-panchang)
- [Moment Panchang](#moment-panchang)
- [Kundli (Birth Chart)](#kundli-birth-chart)
- [Divisional Charts (Vargas)](#divisional-charts-vargas)
- [Vimshottari Dasha](#vimshottari-dasha)
- [Shadbala (Planetary Strength)](#shadbala-planetary-strength)
- [Classical Yogas](#classical-yogas)
- [Festivals](#festivals)
- [Muhurta & Electional](#muhurta--electional)
- [JSON API (PanchangService)](#json-api-panchangservice)
- [Configuration](#configuration)

---

## Installation

```bash
composer require vittix/panchang
```

Requires PHP 8.2+ and PSR-16 cache (optional).

---

## Quick Start

```php
<?php

declare(strict_types=1);

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\ValueObject\GeoLocation;

// Create engine with default settings (Lahiri ayanamsha, pure PHP)
$panchang = Panchang::createDefault();

// Define date, time, and location
$date = new DateTimeImmutable('2026-08-04', new DateTimeZone('Asia/Kolkata'));
$location = new GeoLocation(28.6139, 77.2090); // New Delhi

// Get daily Panchang
$day = $panchang->day($date, $location);

echo "Tithi: " . $day->tithiAtSunrise->nameEnglish() . PHP_EOL;
echo "Nakshatra: " . $day->nakshatraAtSunrise->nameEnglish() . PHP_EOL;
echo "Sunrise: " . $day->solarEvents->sunrise?->format('H:i:s') . PHP_EOL;
```

---

## Daily Panchang

Returns a complete **sunrise-to-sunrise** day (`PanchangDay`):

```php
$day = $panchang->day($date, $location);
// Optional: MonthSystem::Purnimanta for North Indian convention
$day = $panchang->day($date, $location, \Vittix\Panchang\Enum\MonthSystem::Purnimanta);
```

### Five Limbs at Sunrise

```php
$day->tithiAtSunrise;        // Tithi enum
$day->nakshatraAtSunrise;    // Nakshatra enum
$day->padaAtSunrise;         // int (1-4)
$day->yogaAtSunrise;         // Yoga enum
$day->karanaAtSunrise;       // Karana enum
$day->vara;                  // Vara enum (weekday)
```

### Transition Times

```php
$day->tithiEndsAt;           // DateTimeImmutable|null
$day->nakshatraEndsAt;       // DateTimeImmutable|null
$day->yogaEndsAt;            // DateTimeImmutable|null
$day->karanaEndsAt;          // DateTimeImmutable|null
$day->tithiSecond;           // Tithi|null (if two tithis in one day)
$day->tithiSecondEndsAt;     // DateTimeImmutable|null
```

### Solar Events

```php
$day->solarEvents->sunrise;   // DateTimeImmutable|null
$day->solarEvents->sunset;    // DateTimeImmutable|null
$day->solarEvents->solarNoon; // DateTimeImmutable|null
```

### Calendar Context

```php
$day->lunarMonth;            // LunarMonth|null
$day->paksha;                // Paksha::Shukla or Paksha::Krishna
$day->isAdhikaMasa;          // bool
$day->isKshayaMasa;          // bool
```

### Accuracy Info

```php
$day->accuracy->nearBoundary;                    // bool
$day->accuracy->estimatedAngularErrorDegrees;    // float
$day->accuracy->estimatedTimeErrorSeconds;       // float
```

### JSON Output

```php
echo $day->toJson();        // Pretty JSON
echo $day->toJson(0);       // Compact JSON
```

---

## Moment Panchang

Calculate the five limbs for any exact moment:

```php
$moment = new DateTimeImmutable('2026-08-04 14:30:00', new DateTimeZone('Asia/Kolkata'));
$panchangMoment = $panchang->moment($moment, $location);

// Returns PanchangMoment with:
// - tithi, nakshatra, yoga, karana, vara at that instant
// - No solar events (use day() for sunrise/sunset)
```

---

## Kundli (Birth Chart)

Generate a complete Vedic birth chart:

```php
$birthMoment = new DateTimeImmutable('1990-01-01 06:30:00', new DateTimeZone('Asia/Kolkata'));
$kundli = $panchang->kundali($birthMoment, $location);
```

### Ascendant (Lagna)

```php
$asc = $kundli->ascendant;
// $asc->tropicalLongitude (float)
// $asc->siderealLongitude (float)
// $asc->rashi (Rashi enum)
// $asc->degreeInSign (float 0-30)
```

### Planetary Placements

```php
use Vittix\Panchang\Enum\Body;

$jupiter = $kundli->placements[Body::Jupiter->value];
// $jupiter->body (Body enum)
// $jupiter->longitude (float, sidereal)
// $jupiter->rashi (Rashi enum)
// $jupiter->house (int 1-12, whole-sign)
// $jupiter->aspectsHouses (int[])
// $jupiter->dignity (Dignity|null)
// $jupiter->declination (float|null)
// $jupiter->dailyMotion (float|null)
```

### Helper Methods

```php
// Planets in a house
$planetsIn10th = $kundli->getPlacementsInHouse(10);

// Planets in a rashi
$planetsInKarka = $kundli->getPlacementsInRashi(\Vittix\Panchang\Enum\Rashi::Karka);

// Planets aspecting a house
$aspecting10th = $kundli->getPlanetsAspectingHouse(10);

// Planets aspecting a planet
$aspectingMoon = $kundli->getPlanetsAspectingPlanet(Body::Moon);
```

### Ascendant Only

```php
$ascendant = $panchang->ascendant($moment, $location);
// Returns AscendantPosition
```

---

## Divisional Charts (Vargas)

Generate any of the 16 Parashari divisional charts:

```php
use Vittix\Panchang\Enum\Varga;

$navamsha = $panchang->varga($moment, $location, Varga::D9);
$dashamsha = $panchang->varga($moment, $location, Varga::D10);
```

Each returns a complete `Kundli` with recalculated ascendant, planetary positions, houses, dignities, and aspects for that division.

### Supported Vargas

| Varga | Name | Purpose |
|-------|------|---------|
| `D1` | Rasi | Main chart |
| `D2` | Hora | Wealth |
| `D3` | Drekkana | Siblings |
| `D4` | Chaturthamsha | Property |
| `D7` | Saptamsha | Children |
| `D9` | Navamsha | Spouse, Dharma |
| `D10` | Dashamsha | Career |
| `D12` | Dwadashamsha | Parents |
| `D16` | Shodashamsha | Vehicles |
| `D20` | Vimshamsha | Spiritual |
| `D24` | Chaturvimshamsha | Education |
| `D27` | Saptavimshamsha | Strength |
| `D30` | Trimshamsha | Misfortunes |
| `D40` | Khavedamsha | Auspiciousness |
| `D45` | Akshavedamsha | General |
| `D60` | Shashtiamsha | Past Karma |

---

## Vimshottari Dasha

120-year planetary period cycle:

```php
$dashaSequence = $panchang->vimshottariDasha($birthMoment);
// Returns array<DashaPeriod>

foreach ($dashaSequence as $mahadasha) {
    echo $mahadasha->body->name . ": " 
         . $mahadasha->start->format('Y-m-d') . " to " 
         . $mahadasha->end->format('Y-m-d') . PHP_EOL;
    
    // Antardashas (sub-periods)
    foreach ($mahadasha->subPeriods as $antardasha) {
        echo "  " . $antardasha->body->name . ": " 
             . $antardasha->start->format('Y-m-d') . " to " 
             . $antardasha->end->format('Y-m-d') . PHP_EOL;
    }
}
```

### Dasha Order & Durations

| Planet | Years | Nakshatras |
|--------|-------|------------|
| Ketu | 7 | Ashwini, Magha, Mula |
| Venus | 20 | Bharani, Purva Phalguni, Purva Ashadha |
| Sun | 6 | Krittika, Uttara Phalguni, Uttara Ashadha |
| Moon | 10 | Rohini, Hasta, Shravana |
| Mars | 7 | Mrigashira, Chitra, Dhanishtha |
| Rahu | 18 | Ardra, Swati, Shatabhisha |
| Jupiter | 16 | Punarvasu, Vishakha, Purva Bhadrapada |
| Saturn | 19 | Pushya, Anuradha, Uttara Bhadrapada |
| Mercury | 17 | Ashlesha, Jyeshtha, Revati |

---

## Shadbala (Six-Fold Strength)

```php
$shadbala = $panchang->shadbala($moment, $location);

$sunStrength = $shadbala->planets[Body::Sun->value];

echo "Sthana Bala: " . $sunStrength->sthanaBala . " virupas" . PHP_EOL;
echo "Dik Bala: " . $sunStrength->dikBala . " virupas" . PHP_EOL;
echo "Kala Bala: " . $sunStrength->kalaBala . " virupas" . PHP_EOL;
echo "Chesta Bala: " . $sunStrength->chestaBala . " virupas" . PHP_EOL;
echo "Naisargika Bala: " . $sunStrength->naisargikaBala . " virupas" . PHP_EOL;
echo "Drik Bala: " . $sunStrength->drikBala . " virupas" . PHP_EOL;

echo "Total: " . $sunStrength->getTotalVirupas() . " virupas ("
     . $sunStrength->getTotalRupas() . " rupas)" . PHP_EOL;
```

### Components

1. **Sthana Bala** - Positional (house, sign, navamsha)
2. **Dik Bala** - Directional (house placement)
3. **Kala Bala** - Temporal (time, day/night, etc.)
4. **Chesta Bala** - Motional (retrograde, speed)
5. **Naisargika Bala** - Natural (fixed by planet)
6. **Drik Bala** - Aspectual (benefic/malefic aspects)

---

## Classical Yogas

```php
$yogas = $panchang->yogas($moment, $location);
// Returns array<Yoga>

foreach ($yogas as $yoga) {
    echo $yoga->name . PHP_EOL;
    echo $yoga->description . PHP_EOL;
}
```

### Detected Yogas

**Pancha Mahapurusha Yogas:**
- Ruchaka (Mars), Bhadra (Mercury), Hamsa (Jupiter), Malavya (Venus), Sasha (Saturn)

**Other Major Yogas:**
- Gajakesari, Chandra-Mangala, Budha-Aditya, Neecha Bhanga, Viparita Raja, Kahala, Chamara

---

## Festivals

```php
$festivals = $panchang->festivals()->getFestivalsForDate($date, $location);
// Returns array<string> of festival names
```

### Default Festivals

- Gudi Padwa / Ugadi (Chaitra Shukla Pratipada)
- Rama Navami (Chaitra Shukla Navami)
- Akshaya Tritiya (Vaishakha Shukla Tritiya)
- Raksha Bandhan (Shravana Purnima)
- Diwali (Ashwina Amavasya)
- Ganesha Chaturthi (Bhadrapada Shukla Chaturthi, Madhyahna)
- Krishna Janmashtami (Shravana Krishna Ashtami, Nishita)
- Maha Shivaratri (Magha Krishna Chaturdashi, Nishita)

### Custom Festivals

```php
use Vittix\Panchang\Festivals\Festival;
use Vittix\Panchang\Festivals\Rules\SunriseTithiRule;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;

$engine = $panchang->festivals();
$engine->addFestival(new Festival(
    'Custom Festival',
    new SunriseTithiRule(LunarMonth::Kartika, Tithi::ShuklaD11)
));
```

---

## Muhurta & Electional

### Muhurta Calculator

```php
use Vittix\Panchang\Muhurta\MuhurtaCalculator;

$muhurta = $panchang->muhurta(); // or new MuhurtaCalculator()

// Day muhurtas (15)
$dayMuhurtas = $muhurta->getDayMuhurtas($sunrise, $sunset);

// Night muhurtas (15)
$nightMuhurtas = $muhurta->getNightMuhurtas($sunset, $nextSunrise);

// Karmakala (5)
$karmakalas = $muhurta->getKarmakala($sunrise, $sunset);

// Nishita Kaal
$nishita = $muhurta->getNishita($sunset, $nextSunrise);

// Choghadiya (8 day/night)
$dayChoghadiya = $muhurta->getDayChoghadiya($sunrise, $sunset, $weekdayIndex);
$nightChoghadiya = $muhurta->getNightChoghadiya($sunset, $nextSunrise, $weekdayIndex);

// Horas (24)
$horas = $muhurta->getHoras($sunrise, $nextSunrise, $weekdayIndex);
```

### Electional Evaluator

```php
use Vittix\Panchang\Electional\ElectionalEvaluator;

$evaluator = $panchang->electional();

$evaluator->hasAmritSiddhiYoga($weekdayIndex, $nakshatra);
$evaluator->hasSarvarthaSiddhiYoga($weekdayIndex, $nakshatra);
$evaluator->hasGuruPushyaYoga($weekdayIndex, $nakshatra);
$evaluator->hasRaviPushyaYoga($weekdayIndex, $nakshatra);
$evaluator->getDishaShool($weekdayIndex);
$evaluator->isPanchakaDosha($nakshatra);
$evaluator->isBhadraActive($karana);
```

---

## JSON API (PanchangService)

For REST APIs and structured output:

```php
use Vittix\Panchang\Panchanga\PanchangService;

$service = PanchangService::createDefault();
$result = $service->getForDate($date, $location);

echo $result->toJson();  // Complete JSON output
```

### PanchangResult Fields

```json
{
  "date": "2026-08-05",
  "location": {"latitude": 23.0225, "longitude": 72.5714, "elevation": 0},
  "sunrise": "06:12:00",
  "sunset": "18:59:00",
  "moonrise": "23:15:00",
  "moonset": "10:45:00",
  "tithi": {"name": "Krishna Ashtami", "paksha": "Krishna", "endsAt": "15:20:00"},
  "nakshatra": {"name": "Krittika", "pada": 1, "endsAt": "11:10:00"},
  "yoga": {"name": "Shoola", "endsAt": "19:30:00"},
  "karana": {"name": "Bava", "endsAt": "04:15:00"},
  "vara": "Wednesday",
  "lunarMonth": "Shravana",
  "isAdhikaMasa": false,
  "solarMonth": "Karka",
  "samvat": {"vikram": 2083, "shaka": 1948},
  "ayanamsha": 24.1235,
  "accuracy": {"nearBoundary": false, "estimatedAngularErrorDegrees": 0.005, "estimatedTimeErrorSeconds": 10}
}
```

### Astronomy Report

```php
$astro = $service->getAstronomyReport($date, $location);
// Raw astronomy: solar/lunar events, ayanamsha, sidereal time, planetary longitudes
```

---

## Configuration

### Ayanamsha System

```php
use Vittix\Panchang\Enum\AyanamshaSystem;

$panchang = Panchang::createDefault(AyanamshaSystem::Raman);     // Raman
$panchang = Panchang::createDefault(AyanamshaSystem::Krishnamurti); // KP
```

### Caching (Production)

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

$cache = new Psr16Cache(new FilesystemAdapter('panchang', 0, '/tmp/cache'));
$panchang = Panchang::createDefault(AyanamshaSystem::Lahiri, $cache);
```

### Custom Astronomy Engine

```php
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;

$panchang = Panchang::create($customEngine);
```

---

## Key Concepts

- **Sunrise-to-sunrise day**: Hindu day begins at sunrise, not midnight
- **Sidereal zodiac**: All calculations use Nirayana coordinates
- **Whole-sign houses**: House 1 = Ascendant's rashi, etc.
- **Mean nodes**: Rahu/Ketu are mean nodes (standard for Vedic)
- **Date range**: 1900-2100 officially supported
- **Pure PHP**: No extensions, no native libraries required