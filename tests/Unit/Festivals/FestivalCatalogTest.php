<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Festivals;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Festivals\FestivalCatalog;

final class FestivalCatalogTest extends TestCase
{
    public function testDefaultCatalogCanBeBuilt(): void
    {
        $festivals = FestivalCatalog::getDefaultFestivals();

        self::assertNotEmpty($festivals);
    }
}
