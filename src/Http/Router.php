<?php

declare(strict_types=1);

namespace App\Http;

use InvalidArgumentException;

class Router
{
    /** @var array<string, array<string, callable(Request, array<string, string>): Response>> */
    private array $routes = [];
    private const METHODS = ['GET', 'POST'];
    private const PARAMETER_PATTERN = '/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/';

    public function addRoute(string $method, string $path, callable $handler): void
    {
        $method = strtoupper($method);

        if (!in_array($method, self::METHODS, true)) {
            throw new InvalidArgumentException('Unsupported route method: ' . $method);
        }

        $pattern = $this->normalizePattern($path);

        foreach (array_keys($this->routes) as $registeredPath) {
            if ($registeredPath !== $path && $this->normalizePattern($registeredPath) === $pattern) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Route "%s" conflicts with registered route "%s".',
                        $path,
                        $registeredPath,
                    )
                );
            }
        }

        $this->routes[$path][$method] = $handler;
    }

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function dispatch(Request $request): Response
    {
        $match = $this->matchRoute($request->getPath());

        if ($match === null) {
            return new Response(['error' => 'Not found'], 404);
        }

        [$routes, $params] = $match;

        $handler = $routes[$request->getMethod()] ?? null;

        if ($handler === null) {
            $allowedMethods = implode(', ', array_keys($routes));

            return new Response(
                data: ['error' => 'Method not allowed'],
                statusCode: 405,
                headers: ['Allow' => $allowedMethods],
            );
        }

        return $handler($request, $params);
    }

    /**
     * @return array{
     *     0: array<string, callable(Request, array<string, string>): Response>,
     *     1: array<string, string>
     * }|null
     */
    private function matchRoute(string $path): ?array
    {
        if (isset($this->routes[$path])) {
            return [$this->routes[$path], []];
        }

        $pathSegments = explode('/', $path);

        foreach ($this->routes as $routePath => $routes) {
            $routeSegments = explode('/', $routePath);

            if (count($routeSegments) !== count($pathSegments)) {
                continue;
            }

            $params = [];

            foreach ($routeSegments as $index => $segment) {
                if (preg_match(self::PARAMETER_PATTERN, $segment, $matches) === 1) {
                    if ($pathSegments[$index] === '') {
                        continue 2;
                    }

                    $params[$matches[1]] = $pathSegments[$index];
                } elseif ($segment !== $pathSegments[$index]) {
                    continue 2;
                }
            }

            return [$routes, $params];
        }

        return null;
    }

    private function normalizePattern(string $path): string
    {
        $segments = explode('/', $path);

        foreach ($segments as &$segment) {
            if (preg_match(self::PARAMETER_PATTERN, $segment) === 1) {
                $segment = '{}';
            }
        }

        return implode('/', $segments);
    }
}
