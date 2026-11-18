<?php

declare(strict_types=1);

use App\Database\MigrationRunner;
use PHPUnit\Framework\TestCase;

class MigrationRunnerTest extends TestCase
{
    public function testItExecutesTheSqlMigration(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $migrationPath = tempnam(sys_get_temp_dir(), 'migration-');

        file_put_contents(
            $migrationPath,
            'CREATE TABLE charge_points (id INTEGER PRIMARY KEY, name TEXT NOT NULL)',
        );

        try {
            $runner = new MigrationRunner($pdo);
            $runner->run($migrationPath);

            $result = $pdo->query(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'charge_points'",
            )->fetchColumn();

            $this->assertSame('charge_points', $result);
        } finally {
            unlink($migrationPath);
        }
    }

    public function testItThrowsWhenTheMigrationFileDoesNotExist(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $runner = new MigrationRunner($pdo);

        $this->expectException(RuntimeException::class);

        $runner->run('/path/that/does/not/exist.sql');
    }

    public function testItExecutesMultipleSqlStatements(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $migrationPath = tempnam(sys_get_temp_dir(), 'migration-');
        file_put_contents($migrationPath,
            "CREATE TABLE charge_points (id INTEGER PRIMARY KEY, name TEXT NOT NULL);
             INSERT INTO charge_points (name) VALUES ('Amsterdam Central');",
        );

        try {
            (new MigrationRunner($pdo))->run($migrationPath);

            $this->assertSame('Amsterdam Central', $pdo->query('SELECT name FROM charge_points')->fetchColumn());
        } finally {
            unlink($migrationPath);
        }
    }

    public function testItRejectsADirectoryInsteadOfAMigrationFile(): void
    {
        $runner = new MigrationRunner(new PDO('sqlite::memory:'));

        $this->expectException(RuntimeException::class);

        $runner->run(__DIR__);
    }

    public function testItPropagatesSqlErrors(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $migrationPath = tempnam(sys_get_temp_dir(), 'migration-');
        file_put_contents($migrationPath, 'THIS IS NOT VALID SQL;');

        try {
            $this->expectException(PDOException::class);

            (new MigrationRunner($pdo))->run($migrationPath);
        } finally {
            unlink($migrationPath);
        }
    }
}
