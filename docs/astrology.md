# Astrology Features

Vittix Panchang includes a comprehensive Vedic astrology engine beyond basic Panchang calculations.

## Divisional Charts (Vargas / Shodashvargas)

Generate any of the 16 traditional Parashari divisional charts:

```php
use Vittix\Panchang\Enum\Varga;

$navamsha = $panchang->varga($moment, $location, Varga::D9);
$dashamsha = $panchang->varga($moment, $location, Varga::D10);
```

### Supported Vargas

| Varga | Division | Purpose |
|-------|----------|---------|
| `D1` | Rasi | Main chart |
| `D2` | Hora | Wealth |
| `D3` | Drekkana | Siblings, courage |
| `D4` | Chaturthamsha | Property, home |
| `D7` | Saptamsha | Children |
| `D9` | Navamsha | Spouse, dharma (most important) |
| `D10` | Dashamsha | Career, profession |
| `D12` | Dwadashamsha | Parents |
| `D16` | Shodashamsha | Vehicles, comforts |
| `D20` | Vimshamsha | Spiritual progress |
| `D24` | Chaturvimshamsha | Education |
| `D27` | Saptavimshamsha | Strength |
| `D30` | Trimshamsha | Misfortunes |
| `D40` | Khavedamsha | Auspiciousness |
| `D45` | Akshavedamsha | General |
| `D60` | Shashtiamsha | Past karma (most precise) |

Each Varga returns a complete `Kundli` object with recalculated:
- Ascendant for that division
- Planetary positions in new rashis
- House placements (whole-sign from new ascendant)
- Dignities and aspects for the divisional chart

## Vimshottari Dasha (Planetary Periods)

120-year cycle based on Moon's nakshatra at birth:

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

| Planet | Years | Nakshatra Lord |
|--------|-------|----------------|
| Ketu | 7 | Ashwini, Magha, Mula |
| Venus | 20 | Bharani, Purva Phalguni, Purva Ashadha |
| Sun | 6 | Krittika, Uttara Phalguni, Uttara Ashadha |
| Moon | 10 | Rohini, Hasta, Shravana |
| Mars | 7 | Mrigashira, Chitra, Dhanishtha |
| Rahu | 18 | Ardra, Swati, Shatabhisha |
| Jupiter | 16 | Punarvasu, Vishakha, Purva Bhadrapada |
| Saturn | 19 | Pushya, Anuradha, Uttara Bhadrapada |
| Mercury | 17 | Ashlesha, Jyeshtha, Revati |

## Shadbala (Six-Fold Planetary Strength)

Comprehensive strength analysis in Virupas (60 Virupas = 1 Rupa):

```php
$shadbala = $panchang->shadbala($moment, $location);

$sunStrength = $shadbala->planets[Body::Sun->value];

echo "Sthana Bala (Positional): " . $sunStrength->sthanaBala . " virupas" . PHP_EOL;
echo "Dik Bala (Directional): " . $sunStrength->dikBala . " virupas" . PHP_EOL;
echo "Kala Bala (Temporal): " . $sunStrength->kalaBala . " virupas" . PHP_EOL;
echo "Chesta Bala (Motional): " . $sunStrength->chestaBala . " virupas" . PHP_EOL;
echo "Naisargika Bala (Natural): " . $sunStrength->naisargikaBala . " virupas" . PHP_EOL;
echo "Drik Bala (Aspectual): " . $sunStrength->drikBala . " virupas" . PHP_EOL;

echo "Total: " . $sunStrength->getTotalVirupas() . " virupas ("
     . $sunStrength->getTotalRupas() . " rupas)" . PHP_EOL;
```

### Six Components

1. **Sthana Bala** - Positional strength (house, sign, navamsha)
2. **Dik Bala** - Directional strength (house placement)
3. **Kala Bala** - Temporal strength (time of birth, day/night, etc.)
4. **Chesta Bala** - Motional strength (retrograde, speed)
5. **Naisargika Bala** - Natural strength (fixed by planet)
6. **Drik Bala** - Aspectual strength (benefic/malefic aspects)

## Classical Yogas (Planetary Combinations)

Automatic detection of major yogas:

```php
$yogas = $panchang->yogas($moment, $location);
// Returns array<Yoga>

foreach ($yogas as $yoga) {
    echo $yoga->name . PHP_EOL;
    echo $yoga->description . PHP_EOL;
}
```

### Detected Yogas Include

**Pancha Mahapurusha Yogas** (5 great person yogas):
- **Ruchaka** - Mars in own/exalted sign in kendra
- **Bhadra** - Mercury in own/exalted sign in kendra
- **Hamsa** - Jupiter in own/exalted sign in kendra
- **Malavya** - Venus in own/exalted sign in kendra
- **Sasha** - Saturn in own/exalted sign in kendra

**Other Major Yogas**:
- **Gajakesari** - Jupiter in kendra from Moon
- **Chandra-Mangala** - Moon-Mars conjunction
- **Budha-Aditya** - Sun-Mercury conjunction
- **Neecha Bhanga** - Cancellation of debilitation
- **Viparita Raja** - Lords of 6, 8, 12 in mutual exchange
- **Kahala** - 4th/9th lord exchange
- **Chamara** - Lagna lord in kendra with benefic

## Electional Astrology (Muhurta)

Evaluate auspiciousness of a moment:

```php
use Vittix\Panchang\Electional\ElectionalEvaluator;

$evaluator = $panchang->electional();

// Amrit Siddhi Yoga (highly auspicious)
$hasAmritSiddhi = $evaluator->hasAmritSiddhiYoga($weekdayIndex, $nakshatra);

// Sarvartha Siddhi Yoga
$hasSarvarthaSiddhi = $evaluator->hasSarvarthaSiddhiYoga($weekdayIndex, $nakshatra);

// Guru Pushya Yoga (Thursday + Pushya)
$hasGuruPushya = $evaluator->hasGuruPushyaYoga($weekdayIndex, $nakshatra);

// Ravi Pushya Yoga (Sunday + Pushya)
$hasRaviPushya = $evaluator->hasRaviPushyaYoga($weekdayIndex, $nakshatra);

// Disha Shool (direction to avoid for travel)
$avoidDirection = $evaluator->getDishaShool($weekdayIndex);

// Panchaka Dosha (5 inauspicious nakshatras)
$isPanchaka = $evaluator->isPanchakaDosha($nakshatra);

// Bhadra (Vishti Karana) - inauspicious
$isBhadra = $evaluator->isBhadraActive($karana);
```

## Muhurta Calculator

Traditional time divisions for electional work:

```php
use Vittix\Panchang\Muhurta\MuhurtaCalculator;

$muhurta = new MuhurtaCalculator();

// 15 Day Muhurtas (Sunrise to Sunset)
$dayMuhurtas = $muhurta->getDayMuhurtas($sunrise, $sunset);

// 15 Night Muhurtas (Sunset to next Sunrise)
$nightMuhurtas = $muhurta->getNightMuhurtas($sunset, $nextSunrise);

// 5 Karmakala (Pratahkala, Sangava, Madhyahna, Aparahna, Sayahna)
$karmakalas = $muhurta->getKarmakala($sunrise, $sunset);

// Nishita Kaal (8th night muhurta, ~midnight)
$nishita = $muhurta->getNishita($sunset, $nextSunrise);

// Choghadiya (8 segments day/night)
$dayChoghadiya = $muhurta->getDayChoghadiya($sunrise, $sunset, $weekdayIndex);
$nightChoghadiya = $muhurta->getNightChoghadiya($sunset, $nextSunrise, $weekdayIndex);

// Horas (24 planetary hours)
$horas = $muhurta->getHoras($sunrise, $nextSunrise, $weekdayIndex);
```

## Festival Engine

Built-in Hindu festival detection:

```php
$festivals = $panchang->festivals()->getFestivalsForDate($date, $location);
// Returns array<string> of festival names

// Default festivals included:
// - Gudi Padwa / Ugadi (Chaitra Shukla Pratipada)
// - Rama Navami (Chaitra Shukla Navami)
// - Akshaya Tritiya (Vaishakha Shukla Tritiya)
// - Raksha Bandhan (Shravana Purnima)
// - Diwali (Ashwina Amavasya)
// - Ganesha Chaturthi (Bhadrapada Shukla Chaturthi, Madhyahna)
// - Krishna Janmashtami (Shravana Krishna Ashtami, Nishita)
// - Maha Shivaratri (Magha Krishna Chaturdashi, Nishita)
```

### Custom Festivals

```php
use Vittix\Panchang\Festivals\Festival;
use Vittix\Panchang\Festivals\Rules\SunriseTithiRule;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;

$engine = $panchang->festivals();
$engine->addFestival(new Festival(
    'Custom Festival',
    new SunriseTithiRule(LunarMonth::Kartika, Tithi::ShuklaD11) // Kartika Shukla Ekadashi
));
```

## Accuracy & Limitations

- **Date range**: 1900-2100 AD (officially supported)
- **Planetary precision**: < 0.01° (VSOP87/ELP2000 truncated)
- **Ascendant precision**: < 0.01° (Meeus with nutation/obliquity)
- **Ayanamsha**: Exact polynomial (Lahiri/Raman/KP)
- **House system**: Whole-sign only (no Bhava chalit)
- **Divisional charts**: All 16 Parashari vargas
- **Yogas**: Major classical yogas (extensible)
- **Dasha**: Vimshottari only (other dashas not implemented)