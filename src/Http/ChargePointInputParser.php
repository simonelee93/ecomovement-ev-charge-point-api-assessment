<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\ChargePoint\ChargePointStatus;
use App\Domain\ChargePoint\NearbySearch;
use App\Domain\ChargePoint\NewChargePoint;
use InvalidArgumentException;
use JsonException;
use ValueError;

final class ChargePointInputParser
{
    public function parseCreate(string $body): NewChargePoint
    {
        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Invalid request data', previous: $exception);
        }

        if (!is_array($data)
            || !isset($data['name'], $data['latitude'], $data['longitude'], $data['status'])
            || !is_string($data['name'])
            || !is_numeric($data['latitude'])
            || !is_numeric($data['longitude'])
            || !is_string($data['status'])
        ) {
            throw new InvalidArgumentException('Invalid request data');
        }

        try {
            $status = ChargePointStatus::from($data['status']);
        } catch (ValueError $exception) {
            throw new InvalidArgumentException('Invalid request data', previous: $exception);
        }

        return new NewChargePoint(
            $data['name'],
            (float) $data['latitude'],
            (float) $data['longitude'],
            $status,
        );
    }

    public function parseId(mixed $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);

        if ($id === false || $id < 1) {
            throw new InvalidArgumentException('Invalid charge point ID');
        }

        return $id;
    }

    public function parseNearby(array $query): NearbySearch
    {
        if (!isset($query['lat'], $query['lon'], $query['radius'])
            || !is_numeric($query['lat'])
            || !is_numeric($query['lon'])
            || !is_numeric($query['radius'])
        ) {
            throw new InvalidArgumentException('Invalid search parameters');
        }

        return new NearbySearch((float) $query['lat'], (float) $query['lon'], (float) $query['radius']);
    }
}
