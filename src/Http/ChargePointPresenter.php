<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\ChargePoint\ChargePoint;

final class ChargePointPresenter
{
    /** @return array{id: int, name: string, latitude: float, longitude: float, status: string} */
    public function toArray(ChargePoint $chargePoint): array
    {
        return [
            'id' => $chargePoint->getId(),
            'name' => $chargePoint->getName(),
            'latitude' => $chargePoint->getLatitude(),
            'longitude' => $chargePoint->getLongitude(),
            'status' => $chargePoint->getStatus()->value,
        ];
    }
}
