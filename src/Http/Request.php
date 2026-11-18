<?php

declare(strict_types=1);

namespace App\Http;

readonly class Request
{
    private string $method;
    private string $uri;

    public function __construct(string $method, string $uri)
    {
        $this->method = strtoupper($method);
        $this->uri = $uri;
    }

    public static function createFromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return new self(
            $method,
            $uri,
        );
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getPath(): string
    {
        $path = parse_url($this->uri, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
    }

    /**
     * @return array<string|int, mixed>
     */
    public function getQueryParameters(): array
    {
        $query = parse_url($this->uri, PHP_URL_QUERY);
        $params = [];

        if (is_string($query)) {
            parse_str($query, $params);
        }

        return $params;
    }
}
