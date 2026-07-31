<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Localization;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\Vara;
use Vittix\Panchang\Localization\ArrayTranslator;

final class ExtendedLocalesTest extends TestCase
{
    public function testTeluguTranslationLoads(): void
    {
        $translator = new ArrayTranslator(__DIR__ . '/../../../src/Localization');

        self::assertSame('ఆదివారం', $translator->translate(Vara::Ravivara, 'te'));
    }

    public function testKannadaTranslationLoads(): void
    {
        $translator = new ArrayTranslator(__DIR__ . '/../../../src/Localization');

        self::assertSame('ಭಾನುವಾರ', $translator->translate(Vara::Ravivara, 'kn'));
    }

    public function testMalayalamTranslationLoads(): void
    {
        $translator = new ArrayTranslator(__DIR__ . '/../../../src/Localization');

        self::assertSame('ഞായർ', $translator->translate(Vara::Ravivara, 'ml'));
    }
}
