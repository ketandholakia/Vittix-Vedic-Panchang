<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Localization;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\Vara;
use Vittix\Panchang\Localization\ArrayTranslator;

final class ArrayTranslatorTest extends TestCase
{
    public function testTranslateEnglishFallback(): void
    {
        $translator = new ArrayTranslator(__DIR__ . '/../../../src/Localization');
        
        // This will find en/vara.php and translate it
        $result = $translator->translate(Vara::Budhavara, 'en');
        self::assertSame('Wednesday', $result);
    }

    public function testTranslateGujarati(): void
    {
        $translator = new ArrayTranslator(__DIR__ . '/../../../src/Localization');
        
        // This will find gu/vara.php
        $result = $translator->translate(Vara::Budhavara, 'gu');
        self::assertSame('બુધવાર', $result);
    }

    public function testTranslateFallbackToEnglishForMissingLang(): void
    {
        $translator = new ArrayTranslator(__DIR__ . '/../../../src/Localization');
        
        // 'fr' doesn't exist, should fallback to 'en'
        $result = $translator->translate(Vara::Budhavara, 'fr');
        self::assertSame('Wednesday', $result);
    }
}
