<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\Panchang;

final class PanchangTest extends TestCase
{
    public function testJanmarashiFacadeIsAvailable(): void
    {
        $panchang = Panchang::createDefault();
        $moment = new DateTimeImmutable('2026-07-31 00:00:00', new DateTimeZone('UTC'));

        $rashi = $panchang->janmarashi($moment);

        self::assertContains($rashi, Rashi::cases());
    }
}
