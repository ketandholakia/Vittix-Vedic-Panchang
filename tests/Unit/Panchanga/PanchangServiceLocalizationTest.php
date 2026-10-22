<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Panchanga;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use UnitEnum;
use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\Localization\TranslatorInterface;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Translator double that proves the service delegates localization.
 */
final class SpyTranslator implements TranslatorInterface
{
    /** @var array<int, array{enum: UnitEnum, lang: string}> */
    public array $calls = [];

    public function translate(UnitEnum $enum, string $lang = 'en'): string
    {
        $this->calls[] = ['enum' => $enum, 'lang' => $lang];

        return 'translated:' . $enum->name;
    }
}

#[CoversClass(PanchangService::class)]
final class PanchangServiceLocalizationTest extends TestCase
{
    public function testDefaultTranslatorIsArrayBackedAndLocalizes(): void
    {
        $service = PanchangService::createDefault();
        $tithi = \Vittix\Panchang\Enum\Tithi::ShuklaPratipada;

        $english = $service->translate($tithi, 'en');
        $hindi = $service->translate($tithi, 'hi');

        self::assertNotSame('Pratipada', $hindi, 'Hindi translation must come from the bundled dataset');
        self::assertNotSame($english, $hindi);
    }

    public function testCustomTranslatorCanBeRegisteredAndUsed(): void
    {
        $service = PanchangService::createDefault();
        $spy = new SpyTranslator();
        $service->setTranslator($spy);

        self::assertSame('translated:ShuklaPratipada', $service->translate(\Vittix\Panchang\Enum\Tithi::ShuklaPratipada, 'hi'));
        self::assertCount(1, $spy->calls);
        self::assertSame('hi', $spy->calls[0]['lang']);

        // Restoring null falls back to the bundled ArrayTranslator.
        $service->setTranslator(null);
        self::assertNotSame('translated:ShuklaPratipada', $service->translate(\Vittix\Panchang\Enum\Tithi::ShuklaPratipada));
    }

    public function testLocalizedForDateTranslatesAllLimbNames(): void
    {
        $service = PanchangService::createDefault();
        $spy = new SpyTranslator();
        $service->setTranslator($spy);

        $localized = $service->getForDateLocalized(
            new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata')),
            new GeoLocation(latitude: 23.0225, longitude: 72.5714),
            'hi'
        );

        self::assertStringStartsWith('translated:', (string) $localized['tithi']['name']);
        self::assertStringStartsWith('translated:', (string) $localized['nakshatra']['name']);
        self::assertStringStartsWith('translated:', (string) $localized['yoga']['name']);
        self::assertStringStartsWith('translated:', (string) $localized['karana']['name']);
        self::assertStringStartsWith('translated:', (string) $localized['vara']);
        self::assertGreaterThan(5, count($spy->calls), 'all limb names should be translated');
    }
}

