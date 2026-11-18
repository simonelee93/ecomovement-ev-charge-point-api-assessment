<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class ResponseTest extends TestCase
{
    /** @var resource|null */
    private static $server;
    private static string $address;

    public static function setUpBeforeClass(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);

        if ($socket === false) {
            throw new RuntimeException('Cannot allocate a test port: ' . $errorMessage);
        }

        self::$address = stream_socket_get_name($socket, false);
        fclose($socket);

        self::$server = proc_open(
            [PHP_BINARY, '-S', self::$address, __DIR__ . '/fixtures/response.php'],
            [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
        );

        if (!is_resource(self::$server)) {
            throw new RuntimeException('Cannot start the test HTTP server.');
        }

        $deadline = microtime(true) + 5;

        do {
            $connection = @stream_socket_client('tcp://' . self::$address, $errorCode, $errorMessage, 0.1);

            if ($connection !== false) {
                fclose($connection);

                return;
            }

            usleep(10_000);
        } while (microtime(true) < $deadline);

        self::tearDownAfterClass();

        throw new RuntimeException('The test HTTP server did not become ready.');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
            self::$server = null;
        }
    }

    public function testItSendsTheDefaultJsonResponse(): void
    {
        [$body, $headers] = $this->request('/');

        $this->assertStringContainsString('200 OK', $headers[0]);
        $this->assertContains('Content-Type: application/json', $headers);
        $this->assertSame(['status' => 'ok'], json_decode($body, true, 512, JSON_THROW_ON_ERROR));
    }

    public function testItSendsMultipleCustomHeadersAlongsideTheJsonContentType(): void
    {
        [, $headers] = $this->request('/?scenario=custom');

        $this->assertContains('Content-Type: application/json', $headers);
        $this->assertContains('Location: /chargepoints/1', $headers);
        $this->assertContains('X-Request-Id: test-request', $headers);
    }

    public function testCustomHeadersPreserveTheBodyAndStatusCode(): void
    {
        [$body, $headers] = $this->request('/?scenario=custom');

        $this->assertStringContainsString('201 Created', $headers[0]);
        $this->assertSame(
            ['id' => 1, 'name' => 'Amsterdam Central'],
            json_decode($body, true, 512, JSON_THROW_ON_ERROR),
        );
    }

    /** @return array{string, list<string>} */
    private function request(string $path): array
    {
        $context = stream_context_create(['http' => [
            'ignore_errors' => true,
            'follow_location' => 0,
            'timeout' => 5,
        ]]);
        $stream = fopen('http://' . self::$address . $path, 'r', false, $context);

        $this->assertNotFalse($stream);

        try {
            $body = stream_get_contents($stream);
            $headers = stream_get_meta_data($stream)['wrapper_data'];
        } finally {
            fclose($stream);
        }

        $this->assertNotFalse($body);

        return [$body, $headers];
    }
}
