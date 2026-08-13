# Kundli (Birth Chart) Generation

Vittix Panchang provides complete **Kundli (Birth Chart)** generation with authentic Vedic astrology calculations.

## Basic Usage

```php
use Vittix\Panchang\Panchang;
use Vittix\Panchang\ValueObject\GeoLocation;
use DateTimeImmutable;
use DateTimeZone;

$panchang = Panchang::createDefault();
$moment = new DateTimeImmutable('1990-01-01 06:30:00', new DateTimeZone('Asia/Kolkata'));
$location = new GeoLocation(28.6139, 77.2090); // New Delhi

$kundli = $panchang->kundali($moment, $location);
```

## Kundli Structure

The `Kundli` object contains:

### Ascendant (Lagna)
```php
$ascendant = $kundli->ascendant;
// AscendantPosition with:
// - tropicalLongitude: float (0-360°)
// - siderealLongitude: float (0-360°)
// - rashi: Rashi enum (Mesha-Meena)
// - degreeInSign: float (0-30°)
```

### Planetary Placements
```php
use Vittix\Panchang\Enum\Body;

$placements = $kundli->placements; // array<Body::value, PlanetPlacement>

$jupiter = $placements[Body::Jupiter->value];
// PlanetPlacement with:
// - body: Body enum
// - longitude: float (sidereal)
// - rashi: Rashi enum
// - house: int (1-12, whole-sign houses)
// - aspectsHouses: int[] (houses this planet aspects)
// - dignity: Dignity|null (Exalted, Debilitated, Moolatrikona, Own, Friend, Neutral, Enemy)
// - declination: float|null
// - dailyMotion: float|null (degrees/day)
```

## Helper Methods

```php
// Get all planets in a specific house (1-12)
$planetsIn10th = $kundli->getPlacementsInHouse(10);

// Get all planets in a specific rashi
$planetsInKarka = $kundli->getPlacementsInRashi(\Vittix\Panchang\Enum\Rashi::Karka);

// Get planets aspecting a house
$aspecting10th = $kundli->getPlanetsAspectingHouse(10);

// Get planets aspecting a specific planet
$aspectingMoon = $kundli->getPlanetsAspectingPlanet(Body::Moon);
```

## House System

- **Whole-sign houses**: House 1 = Ascendant's rashi, House 2 = next rashi, etc.
- **Bhava chalit**: Not currently implemented (whole-sign only)

## Supported Planets

| Body | Vedic Name | Notes |
|------|------------|-------|
| `Body::Sun` | Surya | |
| `Body::Moon` | Chandra | |
| `Body::Mercury` | Budha | |
| `Body::Venus` | Shukra | |
| `Body::Mars` | Mangal | |
| `Body::Jupiter` | Guru | |
| `Body::Saturn` | Shani | |
| `Body::RahuMean` | Rahu | Mean node (standard for Vedic) |
| `Body::KetuMean` | Ketu | Mean node, always 180° from Rahu |

## Planetary Dignity

Automatic calculation of:
- **Exalted** (Uccha)
- **Debilitated** (Neecha)
- **Moolatrikona**
- **Own Sign** (Swasthana)
- **Friend** (Mitra)
- **Neutral** (Sama)
- **Enemy** (Shatru)

## Planetary Aspects (Drishti)

Standard Parashari aspects:
- All planets aspect 7th house (180°)
- Mars aspects 4th, 7th, 8th
- Jupiter aspects 5th, 7th, 9th
- Saturn aspects 3rd, 7th, 10th
- Rahu/Ketu aspects 5th, 7th, 9th (like Jupiter)

## Ascendant Only

Calculate just the Lagna without full chart:

```php
$ascendant = $panchang->ascendant($moment, $location);
// Returns AscendantPosition
```

## JSON Output

```php
echo $kundli->toJson(); // Not directly implemented, iterate placements
```

## Accuracy

- Ascendant: < 0.01° (using Meeus formulas with nutation/obliquity)
- Planetary positions: Truncated VSOP87/ELP2000, < 0.01° (1900-2100)
- Ayanamsha: Lahiri/Raman/KP polynomial (exact to definition)