# JSON API & Schema

The new `PanchangService` provides a unified engine designed specifically for generating structured, JSON-ready outputs. This makes it ideal for building REST APIs, mobile backends, and decoupled frontends.

## PanchangResult Schema

When calling `PanchangService::getForDate()`, it returns a `PanchangResult` DTO. This DTO implements `JsonSerializable`, so you can simply call `json_encode($result)` or `$result->toJson()`.

### Example Output

```json
{
  "date": "2026-08-05T00:00:00+05:30",
  "location": {
    "latitude": 23.0225,
    "longitude": 72.5714
  },
  "solarEvents": {
    "sunrise": "2026-08-05T06:12:00+05:30",
    "sunset": "2026-08-05T18:59:00+05:30",
    "transit": "2026-08-05T12:35:30+05:30"
  },
  "lunarEvents": {
    "moonrise": "2026-08-05T23:15:00+05:30",
    "moonset": "2026-08-05T10:45:00+05:30",
    "transit": "2026-08-05T17:00:00+05:30"
  },
  "tithi": {
    "index": 22,
    "name": "Krishna Ashtami"
  },
  "tithiEndsAt": "2026-08-05T15:20:00+05:30",
  "tithiSecond": null,
  "tithiSecondEndsAt": null,
  "nakshatra": {
    "index": 2,
    "name": "Krittika"
  },
  "nakshatraPada": 1,
  "nakshatraEndsAt": "2026-08-05T11:10:00+05:30",
  "yoga": {
    "index": 8,
    "name": "Shoola"
  },
  "yogaEndsAt": "2026-08-05T19:30:00+05:30",
  "karana": {
    "index": 0,
    "name": "Bava"
  },
  "karanaEndsAt": "2026-08-05T04:15:00+05:30",
  "vara": {
    "index": 3,
    "name": "Wednesday"
  },
  "paksha": "Krishna",
  "lunarMonth": {
    "index": 4,
    "name": "Shravana"
  },
  "isAdhikaMasa": false,
  "solarMonth": {
    "index": 3,
    "name": "Karka"
  },
  "vikramSamvat": 2083,
  "shakaSamvat": 1948,
  "ayanamsha": 24.123456,
  "accuracy": "High"
}
```

### Properties

- `date`: ISO-8601 string representing the requested day.
- `location`: The `latitude` and `longitude` requested.
- `solarEvents`: Includes `sunrise`, `sunset`, and `transit` (noon) times for the given location in ISO-8601 format.
- `lunarEvents`: Includes `moonrise`, `moonset`, and `transit` times. Can be `null` on days where the event does not occur for the given timezone.
- **Panchanga Elements**: `tithi`, `nakshatra`, `yoga`, and `karana` objects contain their integer `index` (0-indexed) and standard English `name`.
- **Ending Times**: `tithiEndsAt`, `nakshatraEndsAt`, `yogaEndsAt`, and `karanaEndsAt` provide the exact ISO-8601 moment the element ends.
- `vara`: The day of the week object.
- `paksha`: The lunar phase, either `"Shukla"` or `"Krishna"`.
- `lunarMonth` / `isAdhikaMasa`: The Amavasyant lunar month object and a boolean flag indicating if it is an intercalary (leap) month.
- `solarMonth`: The sidereal solar month object.
- `vikramSamvat` / `shakaSamvat`: The calculated Hindu calendar years.
- `ayanamsha`: The calculated longitudinal offset between tropical and sidereal zodiacs.
- `accuracy`: General classification of the algorithm accuracy, typically `"High"`.

## AstronomyResult Schema

The `PanchangService::getAstronomyReport()` method returns a raw astronomy object `AstronomyResult`. It provides identical planetary events without the overhead of Panchang calendar calculations.
