<?php

declare(strict_types=1);

use App\Controller\ChargePointController;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;

return static function (Router $router, ChargePointController $controller): void {
    $router->get('/', fn (Request $request): Response => new Response([
        'application' => 'EV charge point API',
        'status' => 'ok',
        'version' => '1.0.0',
    ]));

    $router->post('/chargepoints', [$controller, 'create']);
    $router->get('/chargepoints/nearby', [$controller, 'nearby']);
    $router->get('/chargepoints/{id}', [$controller, 'show']);
};
