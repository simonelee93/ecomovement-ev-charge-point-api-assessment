<?php

declare(strict_types=1);

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;

require_once __DIR__ . '/../vendor/autoload.php';

$router = new Router();

$router->get('/', fn (Request $request): Response => new Response(
    [
        'application' => 'EV charge point API',
        'status' => 'ok',
        'version' => '1.0.0',
    ],
));

$router->dispatch(Request::createFromGlobals())->send();
