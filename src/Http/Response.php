<?php

declare(strict_types=1);

namespace App\Http;

readonly class Response
{
    private string $body;
    private string $contentType;

    public function __construct(array $data, private int $statusCode = 200, private array $headers = [])
    {
        $this->body = json_encode($data, JSON_THROW_ON_ERROR);
        $this->contentType = 'application/json';
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        header('Content-Type: ' . $this->contentType);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->body;
    }
}
