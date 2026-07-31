<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\Tests\Support\TestLocations;

final class IndependentOracleTest extends TestCase
{
    private Panchang $panchang;
    private DateTimeZone $timezone;
    /**
     * @var array<string, mixed>
     */
    private array $oracle;

    protected function setUp(): void
    {
        $this->panchang = Panchang::createDefault();
        $this->timezone = new DateTimeZone('Asia/Kolkata');

        $path = __DIR__
            . '/../fixtures/oracle/daily_panchang_ahmedabad.json';

        $contents = file_get_contents($path);

        self::assertNotFalse($contents);

        $this->oracle = json_decode(
            $contents,
            true,
            flags: JSON_THROW_ON_ERROR
        );
    }

    /**
     * @dataProvider oracleDateProvider
     */
    public function testAgainstIndependentOracle(
        string $dateString
    ): void {
        $expected = $this->oracle['dates'][$dateString];

        $date = new DateTimeImmutable(
            $dateString . ' 00:00:00',
            $this->timezone
        );

        $actual = $this->panchang->day(
            $date,
            TestLocations::ahmedabad()
        );

        self::assertSame(
            $expected['tithi']['index'],
            $actual->tithiAtSunrise->value
        );

        self::assertSame(
            $expected['tithi']['name_en'],
            $actual->tithiAtSunrise->nameEnglish()
        );

        self::assertSame(
            $expected['tithi']['paksha'],
            $actual->paksha->label()
        );

        self::assertSame(
            $expected['nakshatra']['index'],
            $actual->nakshatraAtSunrise->value
        );

        self::assertSame(
            $expected['nakshatra']['name_en'],
            $actual->nakshatraAtSunrise->nameEnglish()
        );

        self::assertSame(
            $expected['nakshatra']['pada'],
            $actual->padaAtSunrise
        );

        self::assertSame(
            $expected['yoga']['name_en'],
            $actual->yogaAtSunrise->nameEnglish()
        );

        self::assertSame(
            $expected['karana']['name_en'],
            $actual->karanaAtSunrise->nameEnglish()
        );

        self::assertSame(
            $expected['vara']['name_en'],
            $actual->vara->nameEnglish()
        );

        $this->assertTimeWithin(
            $expected['solar_events']['sunrise'],
            $actual->solarEvents->sunrise,
            120,
            'Sunrise'
        );

        $this->assertTimeWithin(
            $expected['solar_events']['sunset'],
            $actual->solarEvents->sunset,
            120,
            'Sunset'
        );

        $this->assertTimeWithin(
            $expected['tithi']['ends_at'],
            $actual->tithiEndsAt,
            120,
            'Tithi ending'
        );

        $this->assertTimeWithin(
            $expected['nakshatra']['ends_at'],
            $actual->nakshatraEndsAt,
            120,
            'Nakshatra ending'
        );
        
        self::assertSame(
            $expected['yoga']['index'],
            $actual->yogaAtSunrise->value
        );
        
        $this->assertTimeWithin(
            $expected['yoga']['ends_at'],
            $actual->yogaEndsAt,
            120,
            'Yoga ending'
        );

        self::assertSame(
            $expected['karana']['index'],
            $actual->karanaAtSunrise->value
        );
        
        $this->assertTimeWithin(
            $expected['karana']['ends_at'],
            $actual->karanaEndsAt,
            120,
            'Karana ending'
        );

        self::assertSame(
            $expected['vara']['index'],
            $actual->vara->value
        );

        self::assertSame(
            $expected['lunar_month']['index'],
            $actual->lunarMonth?->value
        );
        
        self::assertSame(
            $expected['lunar_month']['name_en'],
            $actual->lunarMonth?->nameEnglish()
        );
        
        self::assertSame(
            $expected['lunar_month']['is_adhika'],
            $actual->isAdhikaMasa
        );
    }

    /**
     * @return iterable<int, array{0: string}>
     */
    public static function oracleDateProvider(): iterable
    {
        $path = __DIR__
            . '/../fixtures/oracle/daily_panchang_ahmedabad.json';

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(
                sprintf('Unable to read oracle file: %s', $path)
            );
        }

        $oracle = json_decode(
            $contents,
            true,
            flags: JSON_THROW_ON_ERROR
        );

        foreach (array_keys($oracle['dates']) as $dateString) {
            yield [sprintf('%s', $dateString)];
        }
    }

    private function assertTimeWithin(
        string $expectedIso,
        ?DateTimeImmutable $actual,
        int $toleranceSeconds,
        string $label
    ): void {
        self::assertNotNull(
            $actual,
            sprintf('%s was unexpectedly null', $label)
        );

        $expected = new DateTimeImmutable($expectedIso);
        $difference = abs(
            $expected->getTimestamp() - $actual->getTimestamp()
        );

        self::assertLessThanOrEqual(
            $toleranceSeconds,
            $difference,
            sprintf(
                '%s differed by %d seconds; tolerance is %d seconds',
                $label,
                $difference,
                $toleranceSeconds
            )
        );
    }
}
