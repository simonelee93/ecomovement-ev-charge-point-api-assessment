<?php

declare(strict_types=1);

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testItPassesTheRequestToTheMatchingHandler(): void
    {
        $router = new Router();
        $request = new Request('GET', '/chargepoints?status=AVAILABLE');
        $response = new Response(['id' => 1]);

        $router->get('/chargepoints', function (Request $received) use ($request, $response): Response {
            $this->assertSame($request, $received);

            return $response;
        });

        $this->assertSame($response, $router->dispatch($request));
    }

    public function testItDistinguishesGetAndPostOnTheSamePath(): void
    {
        $router = new Router();
        $getResponse = new Response([]);
        $postResponse = new Response(['id' => 1], 201);
        $router->get('/chargepoints', fn (Request $request): Response => $getResponse);
        $router->post('/chargepoints', fn (Request $request): Response => $postResponse);

        $this->assertSame($getResponse, $router->dispatch(new Request('GET', '/chargepoints')));
        $this->assertSame($postResponse, $router->dispatch(new Request('POST', '/chargepoints')));
    }

    public function testItReturns404ForAnUnknownPath(): void
    {
        $router = new Router();
        $router->get('/chargepoints', fn (Request $request): Response => new Response([]));

        $response = $router->dispatch(new Request('GET', '/chargepoints/unknown'));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Not found'], json_decode($response->getBody(), true));
    }

    public function testItReturns405ForTheWrongMethodOnAKnownPath(): void
    {
        $router = new Router();
        $router->get('/chargepoints', function (): Response {
            $this->fail('The GET handler must not run for a POST request.');
        });

        $response = $router->dispatch(new Request('POST', '/chargepoints'));

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame(
            ['error' => 'Method not allowed'],
            json_decode($response->getBody(), true),
        );
        $this->assertSame('GET', $response->getHeaders()['Allow']);
    }

    public function testItIncludesAllowedMethodsInTheResponseHeader(): void
    {
        $router = new Router();

        $handler = fn (Request $request): Response => new Response([]);

        $router->get('/chargepoints', $handler);
        $router->post('/chargepoints', $handler);

        $response = $router->dispatch(new Request('DELETE', '/chargepoints'));

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('GET, POST', $response->getHeaders()['Allow']);
    }

    public function testItNormalizesTheRegisteredMethod(): void
    {
        $router = new Router();
        $response = new Response([]);
        $router->addRoute('get', '/', fn (Request $request): Response => $response);

        $this->assertSame($response, $router->dispatch(new Request('GET', '/')));
    }

    public function testItRejectsUnsupportedRouteMethods(): void
    {
        $router = new Router();

        $this->expectException(InvalidArgumentException::class);

        $router->addRoute('DELETE', '/', fn (Request $request): Response => new Response([]));
    }

    public function testItMatchesDynamicRoutesAndExtractsParameters(): void
    {
        $router = new Router();

        $router->get('/chargepoints/{id}', function (Request $request, array $params): Response {
            return new Response(['id' => $params['id']]);
        });

        $response = $router->dispatch(new Request('GET', '/chargepoints/123'));

        $this->assertSame(
            ['id' => '123'],
            json_decode($response->getBody(), true),
        );
    }

    public function testItExtractsMultipleParametersWithoutTheQueryString(): void
    {
        $router = new Router();
        $router->get('/locations/{locationId}/chargepoints/{id}',
            fn (Request $request, array $params): Response => new Response($params),
        );

        $response = $router->dispatch(new Request('GET', '/locations/42/chargepoints/123?status=AVAILABLE'));

        $this->assertSame(['locationId' => '42', 'id' => '123'], json_decode($response->getBody(), true));
    }

    public function testItPrefersAnExactRouteOverADynamicRoute(): void
    {
        $router = new Router();
        $router->get('/chargepoints/{id}', fn (): Response => new Response(['route' => 'dynamic']));
        $expected = new Response(['route' => 'nearby']);
        $router->get('/chargepoints/nearby', fn (): Response => $expected);

        $this->assertSame($expected, $router->dispatch(new Request('GET', '/chargepoints/nearby')));
    }

    public function testDynamicParametersMatchExactlyOneNonEmptySegment(): void
    {
        $router = new Router();
        $router->get('/chargepoints/{id}', function (): Response {
            $this->fail('An incomplete or longer path must not match.');
        });

        foreach (['/chargepoints', '/chargepoints/', '/chargepoints/123/extra'] as $path) {
            $this->assertSame(404, $router->dispatch(new Request('GET', $path))->getStatusCode());
        }
    }

    public function testItRejectsEquivalentPatternsWithDifferentParameterNames(): void
    {
        $router = new Router();
        $router->get('/chargepoints/{id}', fn (): Response => new Response([]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('conflicts with');

        $router->get('/chargepoints/{name}', fn (): Response => new Response([]));
    }

    public function testItRejectsEquivalentPatternsEvenForDifferentMethods(): void
    {
        $router = new Router();
        $router->get('/chargepoints/{id}', fn (): Response => new Response([]));

        $this->expectException(InvalidArgumentException::class);

        $router->post('/chargepoints/{name}', fn (): Response => new Response([]));
    }

    public function testMethodsCanShareTheSameDynamicPattern(): void
    {
        $router = new Router();
        $getResponse = new Response(['method' => 'GET']);
        $postResponse = new Response(['method' => 'POST'], 201);
        $router->get('/chargepoints/{id}', fn (): Response => $getResponse);
        $router->post('/chargepoints/{id}', fn (): Response => $postResponse);

        $this->assertSame($getResponse, $router->dispatch(new Request('GET', '/chargepoints/123')));
        $this->assertSame($postResponse, $router->dispatch(new Request('POST', '/chargepoints/123')));
        $this->assertSame('GET, POST', $router->dispatch(new Request('DELETE', '/chargepoints/123'))->getHeaders()['Allow']);
    }

    public function testItAllowsDynamicPatternsWithDifferentLiteralSegments(): void
    {
        $router = new Router();
        $first = new Response(['route' => 'chargepoints']);
        $second = new Response(['route' => 'locations']);
        $router->get('/chargepoints/{id}', fn (): Response => $first);
        $router->get('/locations/{id}', fn (): Response => $second);

        $this->assertSame($first, $router->dispatch(new Request('GET', '/chargepoints/1')));
        $this->assertSame($second, $router->dispatch(new Request('GET', '/locations/1')));
    }

    public function testItReturnsAllowedMethodsForADynamicRoute(): void
    {
        $router = new Router();
        $router->get('/chargepoints/{id}', fn (): Response => new Response([]));

        $response = $router->dispatch(new Request('POST', '/chargepoints/123'));

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('GET', $response->getHeaders()['Allow']);
    }
}
