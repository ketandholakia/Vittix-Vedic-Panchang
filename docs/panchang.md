# Panchang (Daily & Moment Calculations)

Vittix Vedic Panchang provides comprehensive daily and moment-based Panchang calculations based on authentic Vedic astronomy.

## Daily Panchang (`Panchang::day()`)

Returns a complete **sunrise-to-sunrise** daily Panchang (`PanchangDay`):

```php
$day = $panchang->day($date, $location);
```

### Five Limbs (Panchanga) at Sunrise

| Limb | Property | Description |
|------|----------|-------------|
| **Tithi** | `$day->tithiAtSunrise` | Lunar day (30 per month, 15 per paksha) |
| **Nakshatra** | `$day->nakshatraAtSunrise` | Lunar mansion (27 nakshatras) |
| **Yoga** | `$day->yogaAtSunrise` | Sun+Moon combination (27 yogas) |
| **Karana** | `$day->karanaAtSunrise` | Half-tithi (11 karanas) |
| **Vara** | `$day->vara` | Weekday (7 varas) |

### Additional Properties

```php
// Pada (quarter) of nakshatra at sunrise (1-4)
$day->padaAtSunrise;

// Lunar phase
$day->paksha; // Paksha::Shukla or Paksha::Krishna

// Lunar month
$day->lunarMonth; // LunarMonth enum (Chaitra, Vaishakha, etc.)
$day->isAdhikaMasa; // bool - intercalary month
$day->isKshayaMasa; // bool - omitted month

// Transition times (when each limb ends)
$day->tithiEndsAt;           // DateTimeImmutable|null
$day->nakshatraEndsAt;       // DateTimeImmutable|null
$day->yogaEndsAt;            // DateTimeImmutable|null
$day->karanaEndsAt;          // DateTimeImmutable|null

// Second tithi if two occur in one day
$day->tithiSecond;           // Tithi|null
$day->tithiSecondEndsAt;     // DateTimeImmutable|null

// Solar events
$day->solarEvents->sunrise;  // DateTimeImmutable|null
$day->solarEvents->sunset;   // DateTimeImmutable|null
$day->solarEvents->solarNoon; // DateTimeImmutable|null

// Accuracy metadata
$day->accuracy->nearBoundary;           // bool - transition near sunrise
$day->accuracy->estimatedAngularErrorDegrees;
$day->accuracy->estimatedTimeErrorSeconds;
```

## Moment Panchang (`Panchang::moment()`)

Calculate the five limbs for any specific instant:

```php
$moment = new DateTimeImmutable('2026-08-04 14:30:00', new DateTimeZone('Asia/Kolkata'));
$panchangMoment = $panchang->moment($moment, $location);

// Returns PanchangMoment with:
// - tithi, nakshatra, yoga, karana, vara at that exact moment
// - No solar events (use day() for sunrise/sunset)
```

## Janmarashi (Moon Sign)

```php
$moonSign = $panchang->janmarashi($moment);
// Returns Rashi enum (Mesha through Meena)
```

## Calendar Information

```php
// Hindu calendar details for a date
$calendar = $panchang->calendar($date);
// Returns HinduCalendarResult with:
// - vikramSamvat, shakaSamvat
// - lunarMonth, isAdhikaMasa, isKshayaMasa
// - solarMonth (sidereal)
// - ayanamsha value
```

## Month Systems

Supports both **Amanta** (default, ends on Amavasya) and **Purnimanta** (ends on Purnima):

```php
use Vittix\Panchang\Enum\MonthSystem;

$day = $panchang->day($date, $location, MonthSystem::Purnimanta);
```

## JSON Output

Both `PanchangDay` and `PanchangMoment` implement `JsonSerializable`:

```php
echo $day->toJson();        // Pretty JSON
echo $day->toJson(0);       // Compact JSON
```

## Key Concepts

- **Sunrise-to-sunrise day**: Hindu day begins at sunrise, not midnight
- **Sidereal zodiac**: All calculations use Nirayana (sidereal) coordinates
- **Ayanamsha**: Default Lahiri (Chitrapaksha), configurable to Raman or KP
- **High precision**: Truncated VSOP87/ELP2000 series, < 0.01° error (1900-2100)