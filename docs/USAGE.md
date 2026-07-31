# Usage Guide

## Daily Panchang

Use `day()` when you want sunrise-based daily results for a location.

```php
$day = $panchang->day(
    new DateTimeImmutable('2026-07-31', new DateTimeZone('Asia/Kolkata')),
    new GeoLocation(latitude: 23.0225, longitude: 72.5714)
);
```

The returned `PanchangDay` includes the Panchang limbs at sunrise and the solar event window for the date.

## Moment Calculation

Use `moment()` when you want the Panchang state for a specific instant.

```php
$moment = $panchang->moment(
    new DateTimeImmutable('2026-07-31 10:15:00', new DateTimeZone('Asia/Kolkata')),
    new GeoLocation(latitude: 23.0225, longitude: 72.5714)
);
```

## Custom Engine Injection

If you have another astronomy implementation, inject it with `Panchang::create()`.

```php
$panchang = Panchang::create($engine);
```

This is useful for testing, benchmarking, or swapping in a higher-precision engine.

## Verification

Recommended local checks:

```bash
composer quality
```

If you only want tests:

```bash
composer test
```

If you only want static analysis:

```bash
composer analyse
```
