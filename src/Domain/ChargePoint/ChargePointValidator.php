<?php

declare(strict_types=1);

namespace App\Domain\ChargePoint;

use InvalidArgumentException;

final class ChargePointValidator
{
    public static function validate(
        string $name,
        float $latitude,
        float $longitude,
    ): void {
        if (trim($name) === '') {
            throw new InvalidArgumentException('Name cannot be empty.');
        }

        if ($latitude < -90 || $latitude > 90) {
            throw new InvalidArgumentException('Latitude must be between -90 and 90.');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException('Longitude must be between -180 and 180.');
        }
    }
}