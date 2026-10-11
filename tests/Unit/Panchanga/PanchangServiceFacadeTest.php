<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Panchanga;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Electional\ElectionalEvaluator;
use Vittix\Panchang\Enum\Varga;
use Vittix\Panchang\Festivals\FestivalEngine;
use Vittix\Panchang\Muhurta\MuhurtaCalculator;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Verifies that every PanchangService pass-through returns results identical
 * to the equivalent direct call on the shared Panchang facade. The service
 * shares the facade's object graph, so both paths must agree exactly.
 */
#[CoversClass(PanchangService::class)]
final class PanchangServiceFacadeTest extends TestCase
{
    private PanchangService $service;
    private Panchang $facade;
    private GeoLocation $location;
    private DateTimeImmutable $moment;

    protected function setUp(): void
    {
        $this->facade = Panchang::createDefault();
        $this->service = PanchangService::fromPanchang($this->facade);
        $this->location = new GeoLocation(latitude: 23.0225, longitude: 72.5714);
        $this->moment = new DateTimeImmutable('1990-05-15 10:30:00', new DateTimeZone('Asia/Kolkata'));
    }

    public function testDayMatchesFacade(): void
    {
        $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));

        self::assertEquals(
            $this->facade->day($date, $this->location),
            $this->service->day($date, $this->location)
        );
    }

    public function testMomentMatchesFacade(): void
    {
        self::assertEquals(
            $this->facade->moment($this->moment, $this->location),
            $this->service->moment($this->moment, $this->location)
        );
    }

    public function testJanmarashiMatchesFacade(): void
    {
        self::assertSame(
            $this->facade->janmarashi($this->moment),
            $this->service->janmarashi($this->moment)
        );
    }

    public function testFestivalsReturnsEngine(): void
    {
        self::assertInstanceOf(FestivalEngine::class, $this->service->festivals());
    }

    public function testMuhurtaAndElectionalReturnCalculators(): void
    {
        self::assertInstanceOf(MuhurtaCalculator::class, $this->service->muhurta());
        self::assertInstanceOf(ElectionalEvaluator::class, $this->service->electional());
    }

    public function testCalendarMatchesFacade(): void
    {
        self::assertEquals(
            $this->facade->calendar($this->moment),
            $this->service->calendar($this->moment)
        );
    }

    public function testAscendantMatchesFacade(): void
    {
        self::assertEquals(
            $this->facade->ascendant($this->moment, $this->location),
            $this->service->ascendant($this->moment, $this->location)
        );
    }

    public function testKundaliMatchesFacade(): void
    {
        self::assertEquals(
            $this->facade->kundali($this->moment, $this->location),
            $this->service->kundali($this->moment, $this->location)
        );
    }

    public function testVimshottariDashaMatchesFacade(): void
    {
        self::assertEquals(
            $this->facade->vimshottariDasha($this->moment),
            $this->service->vimshottariDasha($this->moment)
        );
    }

    public function testVargaMatchesFacade(): void
    {
        self::assertEquals(
            $this->facade->varga($this->moment, $this->location, Varga::D9),
            $this->service->varga($this->moment, $this->location, Varga::D9)
        );
    }

    public function testShadbalaMatchesFacade(): void
    {
        self::assertEquals(
            $this->facade->shadbala($this->moment, $this->location),
            $this->service->shadbala($this->moment, $this->location)
        );
    }

    public function testYogasMatchFacade(): void
    {
        self::assertEquals(
            $this->facade->yogas($this->moment, $this->location),
            $this->service->yogas($this->moment, $this->location)
        );
    }
}
