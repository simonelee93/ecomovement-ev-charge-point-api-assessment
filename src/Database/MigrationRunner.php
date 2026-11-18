<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;

final readonly class MigrationRunner
{
    public function __construct(private PDO $pdo)
    {
    }

    public function run(string $migrationPath): void
    {
        if (!is_file($migrationPath) || !is_readable($migrationPath)) {
            throw new RuntimeException('Migration file does not exist or is not readable: ' . $migrationPath);
        }

        $sql = file_get_contents($migrationPath);

        if ($sql === false) {
            throw new RuntimeException('Unable to read migration file: ' . $migrationPath);
        }

        if ($this->pdo->exec($sql) === false) {
            throw new RuntimeException('Unable to execute migration: ' . $migrationPath);
        }
    }
}
