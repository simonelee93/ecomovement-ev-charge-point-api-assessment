<?php

declare(strict_types=1);

use App\Database\Connection;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    public function testItCreatesASqliteConnection(): void
    {
        $connection = Connection::create(':memory:');

        $this->assertSame('sqlite', $connection->getAttribute(PDO::ATTR_DRIVER_NAME));
        $this->assertSame(1, $connection->query('SELECT 1')->fetchColumn());
    }

    public function testItEnablesExceptionErrorMode(): void
    {
        $connection = Connection::create(':memory:');

        $this->assertSame(PDO::ERRMODE_EXCEPTION, $connection->getAttribute(PDO::ATTR_ERRMODE));
        $this->expectException(PDOException::class);

        $connection->query('SELECT * FROM missing_table');
    }

    public function testItFetchesAssociativeArraysByDefault(): void
    {
        $connection = Connection::create(':memory:');

        $row = $connection->query("SELECT 1 AS id, 'Amsterdam Central' AS name")->fetch();

        $this->assertSame(['id' => 1, 'name' => 'Amsterdam Central'], $row);
    }

    public function testInMemoryConnectionsAreIndependent(): void
    {
        $first = Connection::create(':memory:');
        $first->exec('CREATE TABLE charge_points (id INTEGER PRIMARY KEY)');

        $second = Connection::create(':memory:');

        $this->assertSame(
            0,
            $second->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'charge_points'")->fetchColumn(),
        );
    }

    public function testItPersistsDataAtTheProvidedDatabasePath(): void
    {
        $databasePath = tempnam(sys_get_temp_dir(), 'connection-test-');
        $this->assertNotFalse($databasePath);

        try {
            $connection = Connection::create($databasePath);
            $connection->exec('CREATE TABLE charge_points (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
            $connection->exec("INSERT INTO charge_points (name) VALUES ('Amsterdam Central')");
            $connection = null;

            $reopened = Connection::create($databasePath);

            $this->assertSame(
                ['id' => 1, 'name' => 'Amsterdam Central'],
                $reopened->query('SELECT id, name FROM charge_points')->fetch(),
            );
        } finally {
            $connection = null;
            $reopened = null;
            unlink($databasePath);
        }
    }

    public function testItThrowsWhenTheDatabasePathCannotBeOpened(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'connection-test-');
        $this->assertNotFalse($filePath);

        try {
            $this->expectException(PDOException::class);

            // A regular file cannot act as the database's parent directory.
            Connection::create($filePath . '/database.sqlite');
        } finally {
            unlink($filePath);
        }
    }
}
