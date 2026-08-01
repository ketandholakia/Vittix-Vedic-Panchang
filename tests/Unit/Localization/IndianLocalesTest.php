<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Localization;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Enum\Vara;
use Vittix\Panchang\Localization\ArrayTranslator;

final class IndianLocalesTest extends TestCase
{
    private function translator(): ArrayTranslator
    {
        return new ArrayTranslator(__DIR__ . '/../../../src/Localization');
    }

    public function testBengaliLocalesLoad(): void
    {
        $translator = $this->translator();

        self::assertSame('রবিবার', $translator->translate(Vara::Ravivara, 'bn'));
        self::assertSame('পূর্ণিমা', $translator->translate(Tithi::Purnima, 'bn'));
        self::assertSame('আশ্বিন', $translator->translate(LunarMonth::Ashwina, 'bn'));
    }

    public function testMarathiLocalesLoad(): void
    {
        $translator = $this->translator();

        self::assertSame('रविवार', $translator->translate(Vara::Ravivara, 'mr'));
        self::assertSame('फाल्गुन', $translator->translate(LunarMonth::Phalguna, 'mr'));
    }

    public function testTeluguLocalesLoad(): void
    {
        $translator = $this->translator();

        self::assertSame('ఆదివారం', $translator->translate(Vara::Ravivara, 'te'));
        self::assertSame('పౌర్ణమి', $translator->translate(Tithi::Purnima, 'te'));
    }

    public function testKannadaLocalesLoad(): void
    {
        $translator = $this->translator();

        self::assertSame('ಭಾನುವಾರ', $translator->translate(Vara::Ravivara, 'kn'));
        self::assertSame('ಚೈತ್ರ', $translator->translate(LunarMonth::Chaitra, 'kn'));
    }

    public function testMalayalamLocalesLoad(): void
    {
        $translator = $this->translator();

        self::assertSame('ഞായർ', $translator->translate(Vara::Ravivara, 'ml'));
        self::assertSame('പൗർണ്ണമി', $translator->translate(Tithi::Purnima, 'ml'));
    }
}
