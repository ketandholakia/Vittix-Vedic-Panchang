# Vittix Vedic Panchang & Astrology Engine - Usage Guide

This library provides a highly precise Vedic astrology engine alongside a traditional Panchanga calculator. It is designed around a simple, easy-to-use facade (`Vittix\Panchang\Panchang`) that internally delegates to complex astronomical and astrological calculators.

## Table of Contents
- [Initialization](#initialization)
- [Panchanga Basics (Tithi, Nakshatra, Karana, Yoga)](#panchanga-basics)
- [Astrology: The Birth Chart (Kundali)](#astrology-the-birth-chart-kundali)
- [Divisional Charts (Shodashvargas)](#divisional-charts-shodashvargas)
- [Vimshottari Dasha (Planetary Periods)](#vimshottari-dasha-planetary-periods)
- [Shadbala (Planetary Strength)](#shadbala-planetary-strength)

---

## Initialization

Everything starts with the `Panchang` facade. You need to provide it with an `AstronomyEngineInterface` (which calculates the underlying planetary math). The library comes with a default implementation using PHP's native `swe_calc_ut` if the `sweph` extension is available, or you can implement your own.

```php
use Vittix\Panchang\Panchang;
use Vittix\Panchang\Astronomy\SwetestAstronomyEngine; // Assuming this exists or similar
use Vittix\Panchang\ValueObject\GeoLocation;

// Initialize the main engine
$astronomyEngine = new SwetestAstronomyEngine();
$panchang = Panchang::create($astronomyEngine);

// Define the location and time for your calculations
$location = new GeoLocation(28.6139, 77.2090, 5.5); // New Delhi (Lat, Lng, Timezone Offset)
$moment = new DateTimeImmutable('1990-01-01 12:00:00', new DateTimeZone('Asia/Kolkata'));
```

---

## Panchanga Basics

The 5 limbs of the Panchanga (Day, Tithi, Nakshatra, Yoga, and Karana) can be calculated easily. 

```php
// Calculate the exact Tithi (Lunar Day)
$tithi = $panchang->tithi($moment);
echo "Tithi: " . $tithi->nameEnglish() . "\n";

// Calculate the Nakshatra (Lunar Mansion)
$nakshatra = $panchang->nakshatra($moment);
echo "Moon is in: " . $nakshatra->nameEnglish() . "\n";

// Get Karana and Yoga
$karana = $panchang->karana($moment);
$yoga = $panchang->yoga($moment);

// Get the Janmarashi (Moon Sign)
$moonSign = $panchang->janmarashi($moment);
echo "Janma Rashi (Moon Sign): " . $moonSign->nameEnglish() . "\n";
```

---

## Astrology: The Birth Chart (Kundali)

You can generate a complete Vedic birth chart (Kundali). The chart automatically computes:
1. Sidereal Longitudes for all planets.
2. The Ascendant (Lagna).
3. The Rashi (Zodiac Sign) placement.
4. House placement using the traditional Whole-Sign house system.
5. Planetary Dignity (Exalted, Debilitated, Moolatrikona, Own Sign).
6. Planetary Aspects (Drishti).

```php
use Vittix\Panchang\Enum\Body;

$kundali = $panchang->kundali($moment, $location);

// The Ascendant
echo "Ascendant is in " . $kundali->ascendant->rashi->nameEnglish() . " at " 
     . round($kundali->ascendant->degreeInSign, 2) . " degrees.\n";

// Inspecting a specific planet
$jupiter = $kundali->placements[Body::Jupiter->value];

echo "Jupiter is in the " . $jupiter->house . "th house.\n";
echo "Jupiter is in the sign of " . $jupiter->rashi->nameEnglish() . ".\n";
echo "Jupiter dignity: " . $jupiter->dignity->value . "\n";

// Aspects
if ($jupiter->aspects[Body::Moon->value]) {
    echo "Jupiter is casting an aspect on the Moon!\n";
}
```

---

## Divisional Charts (Shodashvargas)

Vedic astrology uses 16 different sub-charts to look at specific areas of life. The system supports all 16 traditional Parashari Vargas (D1 through D60).

When you request a Varga, the engine returns a new `Kundali` object. The system automatically mathematically projects the Ascendant and all Planets into their correct signs for that Varga, and then dynamically recalculates the relative Houses, Dignities, and Aspects for the new chart!

```php
use Vittix\Panchang\Enum\Varga;

// Generate the Navamsha (D9) chart - indicates Marriage and Dharma
$navamsha = $panchang->varga($moment, $location, Varga::D9);

$d9Ascendant = $navamsha->ascendant->rashi->nameEnglish();
$d9Venus = $navamsha->placements[Body::Venus->value];

echo "Navamsha Ascendant: $d9Ascendant\n";
echo "Venus is in the " . $d9Venus->house . "th house of the Navamsha chart.\n";

// Generate the Dashamsha (D10) chart - indicates Career and Profession
$dashamsha = $panchang->varga($moment, $location, Varga::D10);
```

---

## Vimshottari Dasha (Planetary Periods)

Generate the 120-year sequence of planetary periods based on the exact position of the Moon in its Nakshatra at birth.

```php
$dashaSequence = $panchang->vimshottariDasha($moment);

foreach ($dashaSequence as $mahadasha) {
    echo "Mahadasha of " . $mahadasha->planet->name . " runs from " 
         . $mahadasha->start->format('Y-m-d') . " to " 
         . $mahadasha->end->format('Y-m-d') . "\n";
         
    // You can also loop through Antardashas (sub-periods)
    foreach ($mahadasha->subPeriods as $antardasha) {
        echo "  - Antardasha of " . $antardasha->planet->name . " ends on " 
             . $antardasha->end->format('Y-m-d') . "\n";
    }
}
```

---

## Shadbala (Planetary Strength)

Shadbala is the complex six-fold system used to evaluate planetary strength. The strength is measured in *Virupas* (where 60 Virupas = 1 Rupa). 

*(Note: Kala, Chesta, and Drik bala are currently stubbed pending astronomy engine expansion).*

```php
$shadbala = $panchang->shadbala($moment, $location);

// Access a specific planet's strength
$sunStrength = $shadbala->planets[Body::Sun->value];

echo "Sun Sthana Bala (Positional): " . $sunStrength->sthanaBala . " virupas\n";
echo "Sun Dik Bala (Directional): " . $sunStrength->dikBala . " virupas\n";
echo "Sun Naisargika Bala (Natural): " . $sunStrength->naisargikaBala . " virupas\n";

echo "Total Sun Strength (Virupas): " . $sunStrength->getTotalVirupas() . "\n";
echo "Total Sun Strength (Rupas): " . $sunStrength->getTotalRupas() . "\n";
```
