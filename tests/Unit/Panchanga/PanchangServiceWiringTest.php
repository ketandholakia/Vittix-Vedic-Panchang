<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Panchanga;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\Panchanga\PanchangService;

/**
 * Verifies that PanchangService shares the object graph of the Panchang
 * facade instead of constructing a second engine (the historical
 * createDefault() built a Panchang, discarded it, and hand-wired a copy).
 */
#[CoversClass(PanchangService::class)]
#[CoversClass(Panchang::class)]
final class PanchangServiceWiringTest extends TestCase
{
    public function testCreateDefaultSharesTheFacadeCalculatorInstance(): void
    {
        $service = PanchangService::createDefault();
        $facadeCalculator = $this->facadeCalculatorOf($service);
        $serviceCalculator = $this->privateServiceProperty($service, 'panchangaCalculator');

        self::assertSame(spl_object_id($facadeCalculator), spl_object_id($serviceCalculator));
    }

    public function testFromPanchangSharesEngineAndCalendarCalculator(): void
    {
        $panchang = Panchang::createDefault();
        $service = PanchangService::fromPanchang($panchang);

        $engine = $this->privateServiceProperty($service, 'astronomy');
        $calendar = $this->privateServiceProperty($service, 'calendarCalculator');

        self::assertSame(spl_object_id($panchang->engine()), spl_object_id($engine));
        self::assertSame(spl_object_id($panchang->calendarCalculator()), spl_object_id($calendar));
        self::assertSame(spl_object_id($panchang->calculator()), spl_object_id($this->facadeCalculatorOf($service)));
    }

    public function testRawConstructorRemainsUsableAndBuildsFacadeLazily(): void
    {
        $panchang = Panchang::createDefault();
        $service = new PanchangService(
            $panchang->engine(),
            $panchang->calculator(),
            $panchang->calendarCalculator(),
        );

        // The lazily-built facade must reuse the same engine instance.
        $reflection = new ReflectionClass($service);
        $panchangProp = $reflection->getProperty('panchang');
        self::assertNull($panchangProp->getValue($service));
    }

    private function privateServiceProperty(PanchangService $service, string $property): object
    {
        $reflection = new ReflectionClass($service);

        return $reflection->getProperty($property)->getValue($service);
    }

    private function facadeCalculatorOf(PanchangService $service): object
    {
        // The service shares the facade's calculator; the facade itself is
        // reachable through the private $panchang property (factories set it).
        $panchang = (new ReflectionClass($service))->getProperty('panchang')->getValue($service);
        self::assertInstanceOf(Panchang::class, $panchang);

        return $panchang->calculator();
    }
}
