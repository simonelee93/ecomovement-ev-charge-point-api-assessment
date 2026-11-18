<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\ChargePoint\ChargePoint;
use App\Domain\ChargePoint\ChargePointStatus;
use App\Domain\ChargePoint\NewChargePoint;
use App\Service\GeoCalculator;
use PDO;

class ChargePointRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly GeoCalculator $geoCalculator = new GeoCalculator(),
    ){
    }

    public function save(NewChargePoint $newChargePoint): ChargePoint
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO charge_points (name, latitude, longitude, status)
             VALUES (:name, :latitude, :longitude, :status)'
        );

        $statement->execute([
            'name' => $newChargePoint->getName(),
            'latitude' => $newChargePoint->getLatitude(),
            'longitude' => $newChargePoint->getLongitude(),
            'status' => $newChargePoint->getStatus()->value,
        ]);

        $id = (int) $this->pdo->lastInsertId();

        return new ChargePoint(
            $id,
            $newChargePoint->getName(),
            $newChargePoint->getLatitude(),
            $newChargePoint->getLongitude(),
            $newChargePoint->getStatus(),
        );
    }

    public function findById(int $id): ?ChargePoint
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, latitude, longitude, status FROM charge_points WHERE id = :id'
        );
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * The radius is in kilometres and includes points on the boundary.
     * Callers must provide valid coordinates and a finite, non-negative radius.
     *
     * @return list<ChargePoint>
     */
    public function findNearby(
        float $latitude,
        float $longitude,
        float $radius
    ): array
    {
        $bounds = $this->geoCalculator->boundingBox(
            $latitude,
            $longitude,
            $radius
        );

        $sql = '
    SELECT id, name, latitude, longitude, status
    FROM charge_points
    WHERE latitude BETWEEN :min_latitude AND :max_latitude
';

        $params = [
            'min_latitude' => $bounds['minLatitude'],
            'max_latitude' => $bounds['maxLatitude'],
        ];

        if (!$bounds['allLongitudes']) {
            if ($bounds['minLongitude'] <= -180.0) {
                // Search crosses the international date line westward.
                $sql .= ' AND (longitude >= :min_longitude OR longitude <= :max_longitude)';
                $params['min_longitude'] = $bounds['minLongitude'] + 360;
                $params['max_longitude'] = $bounds['maxLongitude'];
            } elseif ($bounds['maxLongitude'] >= 180.0) {
                // Search crosses the international date line eastward.
                $sql .= ' AND (longitude >= :min_longitude OR longitude <= :max_longitude)';
                $params['min_longitude'] = $bounds['minLongitude'];
                $params['max_longitude'] = $bounds['maxLongitude'] - 360;
            } else {
                $sql .= ' AND longitude BETWEEN :min_longitude AND :max_longitude';
                $params['min_longitude'] = $bounds['minLongitude'];
                $params['max_longitude'] = $bounds['maxLongitude'];
            }
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        $chargePoints = [];

        while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
            $distance = $this->geoCalculator->distanceKm(
                $latitude,
                $longitude,
                (float) $row['latitude'],
                (float) $row['longitude'],
            );

            if ($distance <= $radius) {
                $chargePoints[] = $this->hydrate($row);
            }
        }

        return $chargePoints;
    }

    private function hydrate(array $row): ChargePoint
    {
        return new ChargePoint(
            (int) $row['id'],
            $row['name'],
            (float) $row['latitude'],
            (float) $row['longitude'],
            ChargePointStatus::from($row['status']),
        );
    }
}
