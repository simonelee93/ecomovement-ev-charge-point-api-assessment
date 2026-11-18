<?php

declare(strict_types=1);

namespace App\Domain\ChargePoint;

readonly class ChargePoint
{
    public function __construct(
        private int $id,
        private string $name,
        private float $latitude,
        private float $longitude,
        private ChargePointStatus $status,
    ){
        ChargePointValidator::validate($name, $latitude, $longitude);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function getStatus(): ChargePointStatus
    {
        return $this->status;
    }
}
