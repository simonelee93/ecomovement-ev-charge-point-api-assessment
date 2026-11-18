<?php

declare(strict_types=1);

use App\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    private array $originalServer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;

        parent::tearDown();
    }

    public function testItCreatesRequestFromGlobals(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/chargepoints',
        ];

        $request = Request::createFromGlobals();

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/chargepoints', $request->getUri());
    }

    public function testItReturnsDefaultsWhenServerVariablesAreMissing(): void
    {
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

        $request = Request::createFromGlobals();

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/', $request->getUri());
    }

    public function testItReturnsTheHttpMethod(): void
    {
        $request = new Request('GET', '/');

        $this->assertSame('GET', $request->getMethod());
    }

    public function testItReturnsTheOriginalUri(): void
    {
        $request = new Request('GET', '/chargepoints?status=AVAILABLE');

        $this->assertSame('/chargepoints?status=AVAILABLE', $request->getUri());
    }

    public function testItExtractsThePathWithoutQueryParameters(): void
    {
        $request = new Request('GET', '/chargepoints?status=AVAILABLE');

        $this->assertSame('/chargepoints', $request->getPath());
    }

    public function testItReturnsQueryParametersAsAnArray(): void
    {
        $request = new Request('GET', '/chargepoints?status=AVAILABLE&lat=53.4&lon=6.49');

        $this->assertSame(['status' => 'AVAILABLE', 'lat' => '53.4', 'lon' => '6.49'], $request->getQueryParameters());
    }

    public function testItReturnsQueryParametersAsAnEmptyArrayForRequestWithoutQueryParameters(): void
    {
        $request = new Request('GET', '/chargepoints');

        $this->assertSame([], $request->getQueryParameters());
    }
}
