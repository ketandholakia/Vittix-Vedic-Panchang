# JSON API & Schema

The `PanchangService` provides a unified engine designed specifically for generating structured, JSON-ready outputs. This makes it ideal for building REST APIs, mobile backends, and decoupled frontends.

## Quick Start

```php
use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\ValueObject\GeoLocation;
use DateTimeImmutable;
use DateTimeZone;

$service = PanchangService::createDefault();
$date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));
$location = new GeoLocation(23.0225, 72.5714); // Ahmedabad

$result = $service->getForDate($date, $location);

// JSON output
header('Content-Type: application/json');
echo $result->toJson();

// Or as array
$data = $result->toArray();
```

## PanchangResult Schema

`PanchangService::getForDate()` returns a `PanchangResult` DTO implementing `JsonSerializable`.

### Complete Example Output

```json
{
  "date": "2026-08-05",
  "location": {
    "latitude": 23.0225,
    "longitude": 72.5714,
    "elevation": 0
  },
  "sunrise": "06:12:00",
  "sunset": "18:59:00",
  "moonrise": "23:15:00",
  "moonset": "10:45:00",
  "tithi": {
    "name": "Krishna Ashtami",
    "paksha": "Krishna",
    "endsAt": "15:20:00",
    "second": null
  },
  "nakshatra": {
    "name": "Krittika",
    "pada": 1,
    "endsAt": "11:10:00"
  },
  "yoga": {
    "name": "Shoola",
    "endsAt": "19:30:00"
  },
  "karana": {
    "name": "Bava",
    "endsAt": "04:15:00"
  },
  "vara": "Wednesday",
  "lunarMonth": "Shravana",
  "isAdhikaMasa": false,
  "isKshayaMasa": false,
  "solarMonth": "Karka",
  "samvat": {
    "vikram": 2083,
    "shaka": 1948
  },
  "ayanamsha": 24.1235,
  "accuracy": {
    "nearBoundary": false,
    "estimatedAngularErrorDegrees": 0.005,
    "estimatedTimeErrorSeconds": 10
  }
}
```

### Field Reference

| Field | Type | Description |
|-------|------|-------------|
| `date` | string (YYYY-MM-DD) | Requested civil date |
| `location` | object | Latitude, longitude, elevation |
| `sunrise` | string (HH:MM:SS) \| null | Local sunrise time |
| `sunset` | string (HH:MM:SS) \| null | Local sunset time |
| `moonrise` | string (HH:MM:SS) \| null | Local moonrise time |
| `moonset` | string (HH:MM:SS) \| null | Local moonset time |
| `tithi.name` | string | English name (e.g., "Krishna Ashtami") |
| `tithi.paksha` | string | "Shukla" or "Krishna" |
| `tithi.endsAt` | string (HH:MM:SS) \| null | When this tithi ends |
| `tithi.second` | object \| null | Second tithi if two in one day |
| `nakshatra.name` | string | English name (e.g., "Krittika") |
| `nakshatra.pada` | int (1-4) | Quarter of nakshatra |
| `nakshatra.endsAt` | string (HH:MM:SS) \| null | When nakshatra ends |
| `yoga.name` | string | English name (e.g., "Shoola") |
| `yoga.endsAt` | string (HH:MM:SS) \| null | When yoga ends |
| `karana.name` | string | English name (e.g., "Bava") |
| `karana.endsAt` | string (HH:MM:SS) \| null | When karana ends |
| `vara` | string | Weekday name (e.g., "Wednesday") |
| `lunarMonth` | string \| null | Lunar month name (e.g., "Shravana") |
| `isAdhikaMasa` | bool | True if intercalary month |
| `isKshayaMasa` | bool | True if omitted month |
| `solarMonth` | string \| null | Sidereal solar month (e.g., "Karka") |
| `samvat.vikram` | int | Vikram Samvat year |
| `samvat.shaka` | int | Shaka Samvat year |
| `ayanamsha` | float | Ayanamsha in degrees |
| `accuracy.nearBoundary` | bool | Transition within 2 min of sunrise |
| `accuracy.estimatedAngularErrorDegrees` | float | Estimated positional error |
| `accuracy.estimatedTimeErrorSeconds` | float | Estimated timing error |

### Time Format

All times are in the **requested timezone** (from the input `DateTimeImmutable`), formatted as `HH:MM:SS` (24-hour).

### Null Values

- `sunrise`/`sunset`/`moonrise`/`moonset`: `null` at extreme latitudes (polar day/night)
- `*EndsAt`: `null` if the limb continues past next sunrise
- `lunarMonth`/`solarMonth`: `null` if calculation unavailable

---

## AstronomyResult Schema

`PanchangService::getAstronomyReport()` returns raw astronomy data without Panchang calendar logic.

```php
$astro = $service->getAstronomyReport($date, $location);
echo $astro->toJson();
```

### Example Output

```json
{
  "date": "2026-08-05",
  "location": {
    "latitude": 23.0225,
    "longitude": 72.5714,
    "elevation": 0
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
  "ayanamsha": 24.123456,
  "siderealTime": 185.4321,
  "sunSiderealLongitude": 108.2345,
  "moonSiderealLongitude": 45.6789,
  "solarMonth": "Karka"
}
```

### Field Reference

| Field | Type | Description |
|-------|------|-------------|
| `solarEvents` | object | ISO-8601 times for sunrise, sunset, transit |
| `lunarEvents` | object | ISO-8601 times for moonrise, moonset, transit |
| `ayanamsha` | float | Ayanamsha in degrees |
| `siderealTime` | float | Local Mean Sidereal Time (degrees) |
| `sunSiderealLongitude` | float | Sun's sidereal longitude (0-360°) |
| `moonSiderealLongitude` | float | Moon's sidereal longitude (0-360°) |
| `solarMonth` | string | Sidereal solar month name |

---

## Integration Examples

### Laravel Controller

```php
<?php

namespace App\Http\Controllers;

use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\ValueObject\GeoLocation;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PanchangController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $date = new DateTimeImmutable($request->date, new DateTimeZone($request->timezone));
        $location = new GeoLocation($request->lat, $request->lng, $request->elevation ?? 0);
        
        $service = PanchangService::createDefault();
        $result = $service->getForDate($date, $location);
        
        return response()->json($result->toArray());
    }
}
```

### Slim Framework

```php
$app->get('/panchang/{date}', function ($request, $response, $args) {
    $date = new DateTimeImmutable($args['date'], new DateTimeZone('Asia/Kolkata'));
    $location = new GeoLocation(28.6139, 77.2090);
    
    $service = PanchangService::createDefault();
    $result = $service->getForDate($date, $location);
    
    $response->getBody()->write($result->toJson());
    return $response->withHeader('Content-Type', 'application/json');
});
```

### Symfony Controller

```php
#[Route('/api/panchang/{date}', methods: ['GET'])]
public function panchang(string $date): JsonResponse
{
    $dateObj = new DateTimeImmutable($date, new DateTimeZone('Europe/Paris'));
    $location = new GeoLocation(48.8566, 2.3522);
    
    $service = PanchangService::createDefault();
    $result = $service->getForDate($dateObj, $location);
    
    return new JsonResponse($result->toArray());
}
```

---

## Caching for Production

```php
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);
$cache = new Psr16Cache(new RedisAdapter($redis));

$service = PanchangService::createDefault(
    \Vittix\Panchang\Enum\AyanamshaSystem::Lahiri,
    $cache
);
```

---

## Error Handling

The service throws standard exceptions:
- `InvalidArgumentException` - Invalid date, location, or parameters
- `RuntimeException` - Calculation failures (rare)

```php
try {
    $result = $service->getForDate($date, $location);
} catch (\InvalidArgumentException $e) {
    // Handle bad input
    return response()->json(['error' => $e->getMessage()], 400);
} catch (\Throwable $e) {
    // Handle calculation errors
    return response()->json(['error' => 'Calculation failed'], 500);
}
```

---

## Performance

| Scenario | Time |
|----------|------|
| First request (cold) | ~50-100ms |
| Cached request | ~5-10ms |
| Batch (100 dates) | ~500ms |

Reuse `PanchangService` instance across requests for best performance.