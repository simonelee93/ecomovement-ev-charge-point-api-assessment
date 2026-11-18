<?php

declare(strict_types=1);

use App\Http\Response;

require dirname(__DIR__, 4) . '/vendor/autoload.php';

if (($_GET['scenario'] ?? '') === 'custom') {
    $response = new Response(
        data: ['id' => 1, 'name' => 'Amsterdam Central'],
        statusCode: 201,
        headers: ['Location' => '/chargepoints/1', 'X-Request-Id' => 'test-request'],
    );
} else {
    $response = new Response(['status' => 'ok']);
}

$response->send();
