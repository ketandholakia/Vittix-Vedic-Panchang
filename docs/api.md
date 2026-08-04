# API

The central entry point is `Vittix\Panchang\Panchang`.

Common methods:

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
