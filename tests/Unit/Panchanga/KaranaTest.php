<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Panchanga;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\Karana;

final class KaranaTest extends TestCase
{
    public function testHalfTithi0IsKimstughna(): void
    {
        self::assertSame(Karana::Kimstughna, Karana::fromHalfTithiIndex(0));
    }

    public function testHalfTithi1IsBava(): void
    {
        self::assertSame(Karana::Bava, Karana::fromHalfTithiIndex(1));
    }

    public function testHalfTithi57IsShakuni(): void
    {
        self::assertSame(Karana::Shakuni, Karana::fromHalfTithiIndex(57));
    }

    public function testHalfTithi58IsChatushpada(): void
    {
        self::assertSame(Karana::Chatushpada, Karana::fromHalfTithiIndex(58));
    }

    public function testHalfTithi59IsNaga(): void
    {
        self::assertSame(Karana::Naga, Karana::fromHalfTithiIndex(59));
    }

    public function testRepeatingCycleWraps(): void
    {
        // Index 8 = (8-1) % 7 = 0 → Bava
        self::assertSame(Karana::Bava, Karana::fromHalfTithiIndex(8));
    }

    public function testVistiIsInauspicious(): void
    {
        self::assertTrue(Karana::Vishti->isInauspicious());
        self::assertFalse(Karana::Bava->isInauspicious());
    }

    public function testFixedKaranas(): void
    {
        self::assertTrue(Karana::Kimstughna->isFixed());
        self::assertTrue(Karana::Shakuni->isFixed());
        self::assertFalse(Karana::Bava->isFixed());
    }
}
