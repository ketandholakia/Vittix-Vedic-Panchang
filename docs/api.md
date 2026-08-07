# API

The central entry points are `Vittix\Panchang\Panchanga\PanchangService` and `Vittix\Panchang\Panchang`.

Common `PanchangService` methods (ideal for JSON APIs):

- `PanchangService::createDefault()`
- `PanchangService::getForDate(DateTimeImmutable $date, GeoLocation $location)`
- `PanchangService::getAstronomyReport(DateTimeImmutable $date, GeoLocation $location)`

Common `Panchang` methods (ideal for internal object-oriented use):

- `Panchang::createDefault()`
- `Panchang::create(AstronomyEngineInterface $engine)`
- `Panchang::day(DateTimeImmutable $date, GeoLocation $location)`
- `Panchang::moment(DateTimeImmutable $moment, GeoLocation $location)`
- `Panchang::janmarashi(DateTimeImmutable $moment)`
- `Panchang::kundali(DateTimeImmutable $moment, GeoLocation $location)`
- `Panchang::varga(DateTimeImmutable $moment, GeoLocation $location, Varga $varga)`
- `Panchang::vimshottariDasha(DateTimeImmutable $moment)`
- `Panchang::shadbala(DateTimeImmutable $moment, GeoLocation $location)`
- `Panchang::yogas(DateTimeImmutable $moment, GeoLocation $location)`
- `Panchang::ascendant(DateTimeImmutable $moment, GeoLocation $location)`

Refer to the `src/` documentation and unit tests for detailed API contracts and return types.
