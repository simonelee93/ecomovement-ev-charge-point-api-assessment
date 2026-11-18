<?php

declare(strict_types=1);

namespace App\Tests\Integration\Database;

use App\Database\Connection;
use App\Database\MigrationRunner;
use PHPUnit\Framework\TestCase;

class MigrationRunnerTest extends TestCase
{
    public function testItCreatesTheChargePointsTable(): void
    {
        $pdo = Connection::create(':memory:');

        $migrationPath = dirname(__DIR__, 3)
            . '/database/migrations/001_create_charge_points.sql';

        $runner = new MigrationRunner($pdo);
        $runner->run($migrationPath);

        $result = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'charge_points'"
        )->fetchColumn();

        $this->assertSame('charge_points', $result);
    }
}
