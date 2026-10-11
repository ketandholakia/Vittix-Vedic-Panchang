# API Reference

Complete reference for the public API surface of Vittix Vedic Panchang.

## Main Facades

### `Vittix\Panchang\Panchang` (Object-Oriented)

Primary facade for programmatic use with rich domain objects.

```php
use Vittix\Panchang\Panchang;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;

// Create with default pure-PHP engine (Lahiri ayanamsha)
$panchang = Panchang::createDefault(
    \Vittix\Panchang\Enum\AyanamshaSystem::Lahiri, // optional
    $cache // optional PSR-16 CacheInterface
);

// Create with custom astronomy engine
$panchang = Panchang::create($customEngine);
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `day(DateTimeImmutable $date, GeoLocation $location, MonthSystem $monthSystem = MonthSystem::Amanta)` | `PanchangDay` | Complete daily Panchang (sunrise-to-sunrise) |
| `moment(DateTimeImmutable $moment, GeoLocation $location)` | `PanchangMoment` | Five limbs at exact moment |
| `janmarashi(DateTimeImmutable $moment)` | `Rashi` | Moon sign (Janmarashi) |
| `calendar(DateTimeImmutable $date)` | `HinduCalendarResult` | Calendar info (Samvat, lunar month, etc.) |
| `ascendant(DateTimeImmutable $moment, GeoLocation $location)` | `AscendantPosition` | Lagna only |
| `kundali(DateTimeImmutable $moment, GeoLocation $location)` | `Kundali` | Full birth chart |
| `varga(DateTimeImmutable $moment, GeoLocation $location, Varga $varga)` | `Kundali` | Divisional chart |
| `vimshottariDasha(DateTimeImmutable $birthTime)` | `array<DashaPeriod>` | 120-year planetary periods |
| `shadbala(DateTimeImmutable $moment, GeoLocation $location)` | `Shadbala` | Six-fold planetary strength |
| `yogas(DateTimeImmutable $moment, GeoLocation $location)` | `array<Yoga>` | Classical planetary yogas |
| `festivals()` | `FestivalEngine` | Festival detection engine |
| `muhurta()` | `MuhurtaCalculator` | Muhurta time divisions |
| `electional()` | `ElectionalEvaluator` | Auspiciousness evaluation |
| `calculator()` | `PanchangaCalculator` | Low-level calculator access |

---

### `Vittix\Panchang\Panchanga\PanchangService` (JSON/API)

Optimized for REST APIs, returns structured DTOs with `toJson()`.

```php
use Vittix\Panchang\Panchanga\PanchangService;

$service = PanchangService::createDefault(
    \Vittix\Panchang\Enum\AyanamshaSystem::Lahiri, // optional
    $cache // optional PSR-16 CacheInterface
);
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getForDate(DateTimeImmutable $date, GeoLocation $location, MonthSystem $monthSystem = MonthSystem::Amanta)` | `PanchangResult` | Complete structured Panchang for API |
| `getAstronomyReport(DateTimeImmutable $date, GeoLocation $location)` | `AstronomyResult` | Raw astronomy data (no Panchang logic) |

The service also exposes pass-throughs for every `Panchang` facade feature
(one engine graph is shared between them — see `PanchangService::fromPanchang()`):

| Method | Returns | Description |
|--------|---------|-------------|
| `day(DateTimeImmutable $date, GeoLocation $location)` | `PanchangDay` | Sunrise-anchored daily Panchang |
| `moment(DateTimeImmutable $moment, GeoLocation $location)` | `PanchangMoment` | Five limbs at an instant |
| `janmarashi(DateTimeImmutable $moment)` | `Rashi` | Moon sign |
| `festivals()` | `FestivalEngine` | Default festival catalog |
| `muhurta()` | `MuhurtaCalculator` | Muhurta evaluation |
| `electional()` | `ElectionalEvaluator` | Electional astrology |
| `calendar(DateTimeImmutable $date)` | `HinduCalendarResult` | Samvat, Adhika Masa context |
| `ascendant(DateTimeImmutable $moment, GeoLocation $location)` | `AscendantPosition` | Lagna |
| `kundali(DateTimeImmutable $moment, GeoLocation $location)` | `Kundali` | Birth chart |
| `vimshottariDasha(DateTimeImmutable $birthTime)` | `array<DashaPeriod>` | Dasha periods |
| `varga(DateTimeImmutable $moment, GeoLocation $location, Varga $varga)` | `Kundali` | Divisional chart |
| `shadbala(DateTimeImmutable $moment, GeoLocation $location)` | `Shadbala` | Six-fold strength |
| `yogas(DateTimeImmutable $moment, GeoLocation $location)` | `array<Yoga>` | Detected yogas |
| `clearCache()` | `void` | Clears the result cache and engine memory caches (PSR-16 untouched) |

`getForDate()` / `getAstronomyReport()` results are memoised in a bounded
512-entry LRU cache keyed by date, timezone, location and month system;
repeat calls with identical inputs return the memoised result. Call
`clearCache()` to force recomputation.

---

## Value Objects

### `GeoLocation`
```php
new GeoLocation(
    float $latitude,      // -90 to +90
    float $longitude,     // -180 to +180
    float $elevationMeters = 0.0
)
```

### `PanchangDay` (from `Panchang::day()`)
```php
$day->date;                    // DateTimeImmutable
$day->timezone;                // DateTimeZone
$day->location;                // GeoLocation
$day->solarEvents;             // SolarEvents (sunrise, sunset, solarNoon)
$day->tithiAtSunrise;          // Tithi enum
$day->nakshatraAtSunrise;      // Nakshatra enum
$day->padaAtSunrise;           // int (1-4)
$day->yogaAtSunrise;           // Yoga enum
$day->karanaAtSunrise;         // Karana enum
$day->vara;                    // Vara enum
$day->tithiEndsAt;             // DateTimeImmutable|null
$day->nakshatraEndsAt;         // DateTimeImmutable|null
$day->yogaEndsAt;              // DateTimeImmutable|null
$day->karanaEndsAt;            // DateTimeImmutable|null
$day->tithiSecond;             // Tithi|null
$day->tithiSecondEndsAt;       // DateTimeImmutable|null
$day->lunarMonth;              // LunarMonth|null
$day->paksha;                  // Paksha enum
$day->isAdhikaMasa;            // bool
$day->isKshayaMasa;            // bool
$day->accuracy;                // AccuracyInfo
$day->toArray();               // array for JSON
$day->toJson();                // JSON string
```

### `PanchangResult` (from `PanchangService::getForDate()`)
```php
$result->date;                 // DateTimeImmutable
$result->location;             // GeoLocation
$result->solarEvents;          // SolarEvents
$result->lunarEvents;          // LunarEvents (moonrise, moonset, transit)
$result->tithi;                // Tithi enum
$result->tithiEndsAt;          // DateTimeImmutable|null
$result->tithiSecond;          // Tithi|null
$result->tithiSecondEndsAt;    // DateTimeImmutable|null
$result->nakshatra;            // Nakshatra enum
$result->nakshatraPada;        // int (1-4)
$result->nakshatraEndsAt;      // DateTimeImmutable|null
$result->yoga;                 // Yoga enum
$result->yogaEndsAt;           // DateTimeImmutable|null
$result->karana;               // Karana enum
$result->karanaEndsAt;         // DateTimeImmutable|null
$result->vara;                 // Vara enum
$result->paksha;               // Paksha enum
$result->lunarMonth;           // LunarMonth|null
$result->isAdhikaMasa;         // bool
$result->isKshayaMasa;         // bool
$result->solarMonth;           // SolarMonth|null
$result->vikramSamvat;         // int
$result->shakaSamvat;          // int
$result->ayanamsha;            // float (degrees)
$result->accuracy;             // AccuracyInfo
$result->toArray();            // array for JSON
$result->toJson();             // JSON string
```

### `Kundali` (from `Panchang::kundali()`)
```php
$kundli->ascendant;            // AscendantPosition
$kundli->placements;           // array<Body::value, PlanetPlacement>
$kundli->getPlacementsInHouse(int $house);        // PlanetPlacement[]
$kundli->getPlacementsInRashi(Rashi $rashi);      // PlanetPlacement[]
$kundli->getPlanetsAspectingHouse(int $house);    // PlanetPlacement[]
$kundli->getPlanetsAspectingPlanet(Body $body);   // PlanetPlacement[]
```

### `PlanetPlacement`
```php
$placement->body;              // Body enum
$placement->longitude;         // float (sidereal)
$placement->rashi;             // Rashi enum
$placement->house;             // int (1-12)
$placement->aspectsHouses;     // int[]
$placement->dignity;           // Dignity|null
$placement->declination;       // float|null
$placement->dailyMotion;       // float|null
```

### `AscendantPosition`
```php
$asc->tropicalLongitude;       // float
$asc->siderealLongitude;       // float
$asc->rashi;                   // Rashi enum
$asc->degreeInSign;            // float (0-30)
```

### `DashaPeriod` (from `vimshottariDasha()`)
```php
$period->body;                 // Body enum
$period->start;                // DateTimeImmutable
$period->end;                  // DateTimeImmutable
$period->subPeriods;           // DashaPeriod[] (antardashas)
```

### `Shadbala` (from `shadbala()`)
```php
$shadbala->planets;            // array<Body::value, ShadbalaValues>
```

### `ShadbalaValues`
```php
$values->body;                 // Body enum
$values->sthanaBala;           // float (virupas)
$values->dikBala;              // float (virupas)
$values->kalaBala;             // float (virupas)
$values->chestaBala;           // float (virupas)
$values->naisargikaBala;       // float (virupas)
$values->drikBala;             // float (virupas)
$values->getTotalVirupas();    // float
$values->getTotalRupas();      // float
```

### `Yoga` (from `yogas()`)
```php
$yoga->name;                   // string
$yoga->description;            // string
```

---

## Enums

### `AyanamshaSystem`
- `Lahiri` (default, Chitrapaksha)
- `Raman`
- `Krishnamurti` (KP)

### `MonthSystem`
- `Amanta` (default, ends Amavasya)
- `Purnimanta` (ends Purnima)

### `Varga` (16 divisions)
`D1`, `D2`, `D3`, `D4`, `D7`, `D9`, `D10`, `D12`, `D16`, `D20`, `D24`, `D27`, `D30`, `D40`, `D45`, `D60`

### `Body`
`Sun`, `Moon`, `Mercury`, `Venus`, `Mars`, `Jupiter`, `Saturn`, `RahuMean`, `KetuMean`

### `Rashi` (12 signs)
`Mesha`, `Vrishabha`, `Mithuna`, `Karka`, `Simha`, `Kanya`, `Tula`, `Vrischika`, `Dhanu`, `Makara`, `Kumbha`, `Meena`

### `Tithi` (30 tithis)
`ShuklaPratipada` (0) through `Purnima` (14), `KrishnaPratipada` (15) through `Amavasya` (29)

### `Nakshatra` (27 nakshatras)
`Ashwini` (0) through `Revati` (26)

### `Yoga` (27 yogas)
`Vishkumbha` (0) through `Vaidhriti` (26)

### `Karana` (11 karanas)
`Bava`, `Balava`, `Kaulava`, `Taitila`, `Garija`, `Vanija`, `Vishti`, `Kimstughna`, `Shakuni`, `Chatushpada`, `Naga`

### `Paksha`
`Shukla`, `Krishna`

### `Vara` (7 weekdays)
`Ravivara` (7/Sun), `Somavara` (1/Mon), `Mangalavara` (2/Tue), `Budhavara` (3/Wed), `Guruvara` (4/Thu), `Shukravara` (5/Fri), `Shanivara` (6/Sat)

### `LunarMonth` (12 months)
`Chaitra`, `Vaishakha`, `Jyeshtha`, `Ashadha`, `Shravana`, `Bhadrapada`, `Ashwina`, `Kartika`, `Margashirsha`, `Pausha`, `Magha`, `Phalguna`

### `SolarMonth` (12 sidereal months)
`Mesha`, `Vrishabha`, `Mithuna`, `Karka`, `Simha`, `Kanya`, `Tula`, `Vrischika`, `Dhanu`, `Makara`, `Kumbha`, `Meena`

### `Dignity`
`Exalted`, `Moolatrikona`, `OwnSign`, `Friend`, `Neutral`, `Enemy`, `Debilitated`

---

## Astronomy Engine

### `AstronomyEngineInterface`
```php
position(Body $body, AstroInstant $instant): EclipticPosition
tropicalLongitude(Body $body, AstroInstant $instant): float
siderealLongitude(Body $body, AstroInstant $instant): float
ayanamsa(AstroInstant $instant): float
equatorialPosition(Body $body, AstroInstant $instant): EquatorialPosition
dailyMotion(Body $body, AstroInstant $instant): float
solarEvents(DateTimeImmutable $date, GeoLocation $location, DateTimeZone $tz): SolarEvents
lunarEvents(DateTimeImmutable $date, GeoLocation $location, DateTimeZone $tz): LunarEvents
siderealTime(AstroInstant $instant, GeoLocation $location): float
clearCache(): void
```

### `EngineFactory::createPurePhpEngine(AyanamshaSystem $system, ?CacheInterface $cache)`
Creates the default pure-PHP engine (no extensions required).

---

## Error Handling

- All methods throw `InvalidArgumentException` for invalid inputs
- `GeoLocation` validates latitude/longitude ranges
- Date range 1900-2100 recommended (works outside with reduced accuracy)
- No exceptions for astronomical edge cases (polar day/night returns null)

---

## Performance Notes

- **First call**: ~50-100ms (cold cache)
- **Subsequent calls**: ~5-10ms (warm cache)
- **Batch operations**: reuse one `Panchang` / `PanchangService` instance — both
  use bounded LRU caches (engine: 512 position entries, configurable via
  `PurePhpAstronomyEngine`'s `$memoryCacheCapacity`; service: 512 results), so
  memory stays flat no matter how many dates you generate. Call `clearCache()`
  only to force recomputation.
- **Caching**: PSR-16 cache persists across requests (recommended for production)