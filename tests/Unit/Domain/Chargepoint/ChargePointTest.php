<?php

declare(strict_types=1);

use App\Domain\ChargePoint\ChargePoint;
use App\Domain\ChargePoint\ChargePointStatus;
use PHPUnit\Framework\TestCase;

class ChargePointTest extends TestCase
{
    public function testItCreatesAChargePointWithAllProperties(): void
    {
        $chargePoint = new ChargePoint(
            id: 1,
            name: 'Amsterdam Central',
            latitude: 52.378,
            longitude: 4.9,
            status: ChargePointStatus::AVAILABLE,
        );

        $this->assertSame(1, $chargePoint->getId());
        $this->assertSame('Amsterdam Central', $chargePoint->getName());
        $this->assertSame(52.378, $chargePoint->getLatitude());
        $this->assertSame(4.9, $chargePoint->getLongitude());
        $this->assertSame(ChargePointStatus::AVAILABLE, $chargePoint->getStatus());
    }

    public function testItRejectsLatitudeBelowMinus90(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ChargePoint(
            id: 1,
            name: 'Amsterdam Central',
            latitude: -90.1,
            longitude: 4.9,
            status: ChargePointStatus::AVAILABLE,
        );
    }

    public function testItRejectsLatitudeAbove90(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ChargePoint(
            id: 1,
            name: 'Amsterdam Central',
            latitude: 90.1,
            longitude: 4.9,
            status: ChargePointStatus::AVAILABLE,
        );
    }

    public function testItRejectsLongitudeBelowMinus180(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ChargePoint(
            id: 1,
            name: 'Amsterdam Central',
            latitude: 52.378,
            longitude: -180.1,
            status: ChargePointStatus::AVAILABLE,
        );
    }

    public function testItRejectsLongitudeAbove180(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ChargePoint(
            id: 1,
            name: 'Amsterdam Central',
            latitude: 52.378,
            longitude: 180.1,
            status: ChargePointStatus::AVAILABLE,
        );
    }

    public function testItRejectsAnEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ChargePoint(
            id: 1,
            name: '',
            latitude: 52.378,
            longitude: 4.9,
            status: ChargePointStatus::AVAILABLE,
        );
    }

    public function testItRejectsANameContainingOnlyWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ChargePoint(
            id: 1,
            name: '   ',
            latitude: 52.378,
            longitude: 4.9,
            status: ChargePointStatus::AVAILABLE,
        );
    }
}
