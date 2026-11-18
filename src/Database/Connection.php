<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

class Connection
{
    public static function create(string $databasePath): PDO
    {
        $pdo = new PDO('sqlite:' . $databasePath);

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }
}