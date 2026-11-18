<?php

declare(strict_types=1);

use App\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testItEncodesDataAsJson(): void
    {
        $data = ['id' => 1, 'status' => 'AVAILABLE'];

        $response = new Response($data);

        $this->assertSame($data, json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testItUsesTheJsonContentType(): void
    {
        $response = new Response(['id' => 1]);

        $this->assertSame('application/json', $response->getContentType());
    }

    public function testItDefaultsToStatusCode200(): void
    {
        $response = new Response(['id' => 1]);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testItUsesACustomStatusCode(): void
    {
        $response = new Response(data: ['id' => 1], statusCode: 201);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testItEncodesAnEmptyListAsAnEmptyJsonArray(): void
    {
        $response = new Response([]);

        $this->assertSame('[]', $response->getBody());
    }

    public function testItPreservesNestedDataAndJsonValueTypes(): void
    {
        $data = [
            'chargepoints' => [
                ['id' => 1, 'name' => 'Ladestation Köln', 'lat' => 50.94, 'available' => true],
            ],
            'next' => null,
        ];

        $response = new Response($data);

        $this->assertSame($data, json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testItThrowsWhenDataCannotBeEncodedAsJson(): void
    {
        $this->expectException(JsonException::class);

        new Response(['name' => "\xB1\x31"]);
    }
}
