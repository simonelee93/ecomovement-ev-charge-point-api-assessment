<?php

declare(strict_types=1);

use App\Controller\ChargePointController;
use App\Database\Connection;
use App\Database\MigrationRunner;
use App\Http\Request;
use App\Http\Router;
use App\Repository\ChargePointRepository;

require_once __DIR__ . '/../vendor/autoload.php';

$config = parse_ini_file(__DIR__ . '/../.env', false, INI_SCANNER_RAW);

if ($config === false) {
    throw new RuntimeException('Unable to load application configuration.');
}

$databasePath = __DIR__ . '/../' . $config['DB_PATH'];

$databaseDirectory = dirname($databasePath);

if (!is_dir($databaseDirectory)) {
    mkdir($databaseDirectory, 0775, true);
}

$pdo = Connection::create($databasePath);

$migrationPath = __DIR__ . '/../database/migrations/001_create_charge_points.sql';

(new MigrationRunner($pdo))->run($migrationPath);

$repository = new ChargePointRepository($pdo);
$controller = new ChargePointController($repository);

$router = new Router();

$registerRoutes = require __DIR__ . '/../config/routes.php';
$registerRoutes($router, $controller);

$router->dispatch(Request::createFromGlobals())->send();
