<?php

declare(strict_types=1);

namespace App\Domain\ChargePoint;

use InvalidArgumentException;

final readonly class NearbySearch
{
    /** Radius is in kilometres. */
    public function __construct(
        public float $latitude,
        public float $longitude,
        public float $radius,
    ) {
        if (!is_finite($latitude) || $latitude < -90 || $latitude > 90
            || !is_finite($longitude) || $longitude < -180 || $longitude > 180
            || !is_finite($radius) || $radius < 0
        ) {
            throw new InvalidArgumentException('Invalid search parameters');
        }
    }
}
