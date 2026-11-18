<?php

declare(strict_types=1);

namespace App\tests\Integration\Repository;

use App\Database\Connection;
use App\Database\MigrationRunner;
use App\Domain\ChargePoint\ChargePoint;
use App\Domain\ChargePoint\ChargePointStatus;
use App\Domain\ChargePoint\NewChargePoint;
use App\Repository\ChargePointRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class ChargePointRepositoryTest extends TestCase
{
    private PDO $pdo;
    private ChargePointRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = Connection::create(':memory:');

        (new MigrationRunner($this->pdo))->run(
            dirname(__DIR__, 3) . '/database/migrations/001_create_charge_points.sql'
        );

        $this->repository = new ChargePointRepository($this->pdo);
    }

    public function testItFindsTheChargePointWithTheRequestedId(): void
    {
        $this->pdo->exec("INSERT INTO charge_points (id, name, latitude, longitude, status) VALUES
            (1, 'Other station', 0, 0, 'AVAILABLE'),
            (2, 'Amsterdam Central', 52.378, 4.9, 'OCCUPIED')");

        $chargePoint = $this->repository->findById(2);

        $this->assertInstanceOf(ChargePoint::class, $chargePoint);
        $this->assertSame(2, $chargePoint->getId());
        $this->assertSame('Amsterdam Central', $chargePoint->getName());
        $this->assertSame(52.378, $chargePoint->getLatitude());
        $this->assertSame(4.9, $chargePoint->getLongitude());
        $this->assertSame(ChargePointStatus::OCCUPIED, $chargePoint->getStatus());
    }

    public function testItReturnsNullWhenTheIdDoesNotExist(): void
    {
        $this->pdo->exec("INSERT INTO charge_points (id, name, latitude, longitude, status)
            VALUES (1, 'Amsterdam Central', 52.378, 4.9, 'AVAILABLE')");

        $this->assertNull($this->repository->findById(999));
    }

    public function testItSavesANewChargePointAndReturnsItWithAnId(): void
    {
        $newChargePoint = new NewChargePoint(
            'Amsterdam Central',
            52.378,
            4.900,
            ChargePointStatus::AVAILABLE,
        );

        $chargePoint = $this->repository->save($newChargePoint);

        $this->assertInstanceOf(ChargePoint::class, $chargePoint);
        $this->assertSame(1, $chargePoint->getId());
        $this->assertSame('Amsterdam Central', $chargePoint->getName());
        $this->assertSame(52.378, $chargePoint->getLatitude());
        $this->assertSame(4.900, $chargePoint->getLongitude());
        $this->assertSame(ChargePointStatus::AVAILABLE, $chargePoint->getStatus());
    }

    public function testItReturnsAnEmptyArrayWhenNoPointsAreInRange(): void
    {
        $this->assertSame([], $this->repository->findNearby(0, 0, 5));

        $this->pdo->exec("INSERT INTO charge_points (name, latitude, longitude, status)
            VALUES ('Far away', 52.378, 4.9, 'AVAILABLE')");

        $this->assertSame([], $this->repository->findNearby(0, 0, 5));
    }

    public function testZeroRadiusIncludesOnlyPointsAtTheSearchCoordinates(): void
    {
        $this->pdo->exec("INSERT INTO charge_points (name, latitude, longitude, status) VALUES
            ('At centre', 0, 0, 'OFFLINE'),
            ('Outside', 0.001, 0, 'AVAILABLE')");

        $points = $this->repository->findNearby(0, 0, 0);

        $this->assertCount(1, $points);
        $this->assertSame('At centre', $points[0]->getName());
        $this->assertSame(ChargePointStatus::OFFLINE, $points[0]->getStatus());
    }

    public function testItFindsPointsAcrossTheInternationalDateLine(): void
    {
        $this->pdo->exec("INSERT INTO charge_points (name, latitude, longitude, status)
            VALUES ('Across date line', 0, -179.99, 'AVAILABLE')");

        $points = $this->repository->findNearby(0, 179.99, 3);

        $this->assertCount(1, $points);
        $this->assertSame('Across date line', $points[0]->getName());
        $this->assertSame([], $this->repository->findNearby(0, 179.99, 2));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dateLineBoundarySearches')]
    public function testItIncludesEquivalentDateLineLongitudesAtTheBoundary(
        float $searchLongitude,
        float $storedLongitude,
        float $radius,
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO charge_points (name, latitude, longitude, status) VALUES (:name, 0, :longitude, :status)'
        );
        $statement->execute([
            'name' => 'Date line station',
            'longitude' => $storedLongitude,
            'status' => 'AVAILABLE',
        ]);

        $points = $this->repository->findNearby(0, $searchLongitude, $radius);

        $this->assertCount(1, $points);
        $this->assertSame('Date line station', $points[0]->getName());
        $this->assertSame($storedLongitude, $points[0]->getLongitude());
    }

    public static function dateLineBoundarySearches(): array
    {
        $oneDegreeKm = (new \App\Service\GeoCalculator())->distanceKm(0, 0, 0, 1);

        return [
            'positive 180 at zero radius' => [180.0, -180.0, 0.0],
            'negative 180 at zero radius' => [-180.0, 180.0, 0.0],
            'eastern boundary' => [179.0, -180.0, $oneDegreeKm],
            'western boundary' => [-179.0, 180.0, $oneDegreeKm],
        ];
    }

    public function testItFindsChargePointsWithinTheRequestedRadius(): void
    {
        $this->pdo->exec("
        INSERT INTO charge_points (name, latitude, longitude, status) VALUES
        ('Nearby station', 52.379, 4.900, 'AVAILABLE'),
        ('Faraway station', 52.520, 13.405, 'OCCUPIED')
    ");

        $chargePoints = $this->repository->findNearby(
            52.378,
            4.900,
            5.0
        );

        $this->assertCount(1, $chargePoints);
        $this->assertInstanceOf(ChargePoint::class, $chargePoints[0]);
        $this->assertSame('Nearby station', $chargePoints[0]->getName());
    }

    public function testItFindsNearbyChargePointsAcrossTheInternationalDateLine(): void
    {
        $this->pdo->exec("
        INSERT INTO charge_points (name, latitude, longitude, status) VALUES
        ('Nearby station', 0, -179.99, 'AVAILABLE'),
        ('Faraway station', 0, 170, 'OCCUPIED')
    ");

        $chargePoints = $this->repository->findNearby(
            0,
            179.99,
            3.0
        );

        $this->assertCount(1, $chargePoints);
        $this->assertSame('Nearby station', $chargePoints[0]->getName());
    }

    public function testItExcludesPointsInsideBoundingBoxButOutsideRadius(): void
    {
        $this->pdo->exec("
        INSERT INTO charge_points (name, latitude, longitude, status) VALUES
        ('Nearby station', 0, 0.01, 'AVAILABLE'),
        ('Outside radius', 0.04, 0.04, 'OCCUPIED')
    ");

        $chargePoints = $this->repository->findNearby(
            0,
            0,
            3.0
        );

        $this->assertCount(1, $chargePoints);
        $this->assertSame('Nearby station', $chargePoints[0]->getName());
    }

    public function testItIncludesPointsExactlyOnTheRadiusBoundary(): void
    {
        $this->pdo->exec("
        INSERT INTO charge_points (name, latitude, longitude, status)
        VALUES ('Boundary station', 0, 0.01, 'AVAILABLE')
    ");

        $chargePoints = $this->repository->findNearby(
            0,
            0,
            1.11195
        );

        $this->assertCount(1, $chargePoints);
        $this->assertSame('Boundary station', $chargePoints[0]->getName());
    }
}
