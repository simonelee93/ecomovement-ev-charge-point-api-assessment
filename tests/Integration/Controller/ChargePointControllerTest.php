<?php

declare(strict_types=1);

namespace Tests\Integration\Controller;

use App\Controller\ChargePointController;
use App\Database\Connection;
use App\Database\MigrationRunner;
use App\Domain\ChargePoint\ChargePointStatus;
use App\Http\Request;
use App\Repository\ChargePointRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class ChargePointControllerTest extends TestCase
{
    private PDO $pdo;
    private ChargePointRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = Connection::create(':memory:');

        $migrationPath = dirname(__DIR__, 3)
            . '/database/migrations/001_create_charge_points.sql';

        (new MigrationRunner($this->pdo))->run($migrationPath);

        $this->repository = new ChargePointRepository($this->pdo);
    }

    public function testItCreatesAChargePoint(): void
    {
        $controller = new ChargePointController($this->repository);

        $request = new Request(
            'POST',
            '/chargepoints',
            json_encode([
                'name' => 'Amsterdam Central',
                'latitude' => 52.378,
                'longitude' => 4.9,
                'status' => 'AVAILABLE',
            ], JSON_THROW_ON_ERROR),
        );

        $response = $controller->create($request);

        $this->assertSame(201, $response->getStatusCode());

        $data = json_decode(
            $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(1, $data['id']);
        $this->assertSame('Amsterdam Central', $data['name']);
        $this->assertSame(52.378, $data['latitude']);
        $this->assertSame(4.9, $data['longitude']);
        $this->assertSame(ChargePointStatus::AVAILABLE->value, $data['status']);
    }

    public function testItReturnsBadRequestForMalformedJson(): void
    {
        $controller = new ChargePointController($this->repository);

        $request = new Request(
            'POST',
            '/chargepoints',
            '{"name": "Amsterdam Central"',
        );

        $response = $controller->create($request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testItReturnsDomainValidationErrorsWithoutSaving(): void
    {
        $controller = new ChargePointController($this->repository);
        $response = $controller->create(new Request('POST', '/chargepoints',
            '{"name":" ","latitude":0,"longitude":0,"status":"AVAILABLE"}',
        ));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Name cannot be empty.'], json_decode($response->getBody(), true));
        $this->assertSame(0, $this->pdo->query('SELECT COUNT(*) FROM charge_points')->fetchColumn());
    }

    public function testItReturns400ForAnInvalidId(): void
    {
        $response = (new ChargePointController($this->repository))->show(
            new Request('GET', '/chargepoints/abc'), ['id' => 'abc'],
        );

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Invalid charge point ID'], json_decode($response->getBody(), true));
    }

    public function testItReturns404ForAMissingChargePoint(): void
    {
        $response = (new ChargePointController($this->repository))->show(
            new Request('GET', '/chargepoints/1'), ['id' => '1'],
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testItReturns400ForInvalidSearchValues(): void
    {
        $response = (new ChargePointController($this->repository))->nearby(
            new Request('GET', '/chargepoints/nearby?lat=91&lon=0&radius=5'),
        );

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Invalid search parameters'], json_decode($response->getBody(), true));
    }

    public function testItDoesNotReportDatabaseFailuresAsInvalidInput(): void
    {
        $this->pdo->exec('DROP TABLE charge_points');
        $controller = new ChargePointController($this->repository);

        $this->expectException(\PDOException::class);

        $controller->create(new Request('POST', '/chargepoints',
            '{"name":"Station","latitude":0,"longitude":0,"status":"AVAILABLE"}',
        ));
    }

    public function testItReturnsAChargePointById(): void
    {
        $this->pdo->exec("
        INSERT INTO charge_points (name, latitude, longitude, status)
        VALUES ('Amsterdam Central', 52.378, 4.9, 'AVAILABLE')
    ");

        $controller = new ChargePointController($this->repository);

        $request = new Request('GET', '/chargepoints/1');

        $response = $controller->show($request, ['id' => '1']);

        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode(
            $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(1, $data['id']);
        $this->assertSame('Amsterdam Central', $data['name']);
        $this->assertSame('AVAILABLE', $data['status']);
    }

    public function testItReturnsNearbyChargePoints(): void
    {
        $this->pdo->exec("
        INSERT INTO charge_points (name, latitude, longitude, status)
        VALUES ('Amsterdam Central', 52.378, 4.9, 'AVAILABLE')
    ");

        $controller = new ChargePointController($this->repository);

        $request = new Request(
            'GET',
            '/chargepoints/nearby?lat=52.378&lon=4.9&radius=5',
        );

        $response = $controller->nearby($request);

        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode(
            $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertCount(1, $data);
        $this->assertSame('Amsterdam Central', $data[0]['name']);
    }
}
