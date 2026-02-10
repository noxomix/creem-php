<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Unit;

use Noxomix\CreemPhp\Config\EnvMode;
use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Exception\NetworkException;
use Noxomix\CreemPhp\Exception\RateLimitException;
use Noxomix\CreemPhp\Exception\ValidationException;
use Noxomix\CreemPhp\Http\HttpResponse;
use Noxomix\CreemPhp\Http\RequestOptions;
use Noxomix\CreemPhp\Tests\Support\FakeSleeper;
use Noxomix\CreemPhp\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

final class CreemClientTest extends TestCase
{
    public function test_it_accepts_array_configuration_in_constructor(): void
    {
        $client = new CreemClient([
            'api_key' => 'creem_test_key',
        ]);

        $this->assertSame('test', $client->mode());
        $this->assertSame('https://test-api.creem.io/v1/checkouts', $client->endpoint('/v1/checkouts'));
    }

    public function test_it_allows_explicit_mode_enum_input(): void
    {
        $client = new CreemClient([
            'api_key' => 'creem_test_key',
            'mode' => EnvMode::TEST,
        ]);

        $this->assertSame('test', $client->mode());
    }

    public function test_it_throws_for_missing_api_key_in_array_configuration(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new CreemClient([
            'mode' => 'test',
        ]);
    }

    public function test_it_builds_absolute_endpoints(): void
    {
        $client = new CreemClient([
            'api_key' => 'creem_test_key',
            'mode' => 'test',
        ]);

        $this->assertSame(
            'https://test-api.creem.io/v1/checkouts',
            $client->endpoint('/v1/checkouts'),
        );
    }

    public function test_it_sends_request_with_default_headers_and_request_id(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"ok":true}'),
        ]);

        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
            ],
            transport: $transport,
        );

        $response = $client->rawRequest(
            method: 'POST',
            path: '/v1/checkouts',
            body: ['product_id' => 'prod_1'],
            requestId: 'checkout_123',
        );

        $this->assertSame(['ok' => true], $response);
        $request = $transport->requests()[0];
        $this->assertSame('POST', $request->method());
        $this->assertSame('/v1/checkouts', $request->path());
        $this->assertSame('creem_test_key', $request->headers()['x-api-key']);
        $this->assertSame('application/json', $request->headers()['accept']);
        $this->assertSame('application/json', $request->headers()['content-type']);
        $this->assertSame('prod_1', $request->body()['product_id']);
        $this->assertSame('checkout_123', $request->body()['request_id']);
        $this->assertSame(10.0, $request->connectTimeoutSeconds());
        $this->assertSame(30.0, $request->requestTimeoutSeconds());
    }

    public function test_it_supports_request_options_object_for_safe_request_building(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"ok":true}'),
        ]);

        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
            ],
            transport: $transport,
        );

        $response = $client->request(new RequestOptions(
            method: 'POST',
            path: '/v1/checkouts',
            body: ['product_id' => 'prod_1'],
            requestId: 'checkout_456',
        ));

        $this->assertSame(['ok' => true], $response);
        $request = $transport->requests()[0];
        $this->assertSame('POST', $request->method());
        $this->assertSame('/v1/checkouts', $request->path());
        $this->assertSame('checkout_456', $request->body()['request_id']);
    }

    public function test_it_does_not_add_request_id_to_get_requests(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"id":"chk_1"}'),
        ]);

        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
            ],
            transport: $transport,
        );

        $client->rawRequest(
            method: 'GET',
            path: '/v1/checkouts',
            query: ['checkout_id' => 'chk_1'],
            requestId: 'ignored_request_id',
        );

        $request = $transport->requests()[0];
        $this->assertNull($request->body());
        $this->assertSame(['checkout_id' => 'chk_1'], $request->query());
    }

    public function test_it_does_not_add_request_id_when_not_provided_for_post_requests(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"ok":true}'),
        ]);

        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
            ],
            transport: $transport,
        );

        $client->rawRequest(
            method: 'POST',
            path: '/v1/products',
            body: ['name' => 'Starter'],
        );

        $request = $transport->requests()[0];
        $this->assertArrayNotHasKey('request_id', $request->body());
    }

    public function test_it_retries_rate_limit_responses_and_then_returns_success(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(
                429,
                [],
                '{"trace_id":"trace_1","status":429,"error":"Too Many Requests","message":["Retry later"],"code":"rate_limited"}',
            ),
            new HttpResponse(200, [], '{"ok":true}'),
        ]);
        $sleeper = new FakeSleeper();

        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
                'max_retries' => 2,
                'retry_base_delay_ms' => 1000,
                'retry_max_delay_ms' => 1000,
            ],
            transport: $transport,
            sleeper: $sleeper,
        );

        $response = $client->rawRequest('GET', '/v1/transactions', ['transaction_id' => 'txn_1']);

        $this->assertSame(['ok' => true], $response);
        $this->assertCount(2, $transport->requests());
        $this->assertCount(1, $sleeper->sleptMilliseconds());
        $this->assertGreaterThanOrEqual(1000, $sleeper->sleptMilliseconds()[0]);
        $this->assertLessThanOrEqual(1250, $sleeper->sleptMilliseconds()[0]);
    }

    public function test_it_retries_network_errors_and_then_returns_success(): void
    {
        $transport = new FakeTransport([
            NetworkException::fromThrowable(new \RuntimeException('connection reset')),
            new HttpResponse(200, [], '{"ok":true}'),
        ]);
        $sleeper = new FakeSleeper();

        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
                'max_retries' => 1,
                'retry_base_delay_ms' => 500,
                'retry_max_delay_ms' => 500,
            ],
            transport: $transport,
            sleeper: $sleeper,
        );

        $response = $client->rawRequest('GET', '/v1/customers', ['customer_id' => 'cus_1']);

        $this->assertSame(['ok' => true], $response);
        $this->assertCount(2, $transport->requests());
        $this->assertCount(1, $sleeper->sleptMilliseconds());
    }

    public function test_it_throws_typed_validation_exception_with_diagnostics(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(
                400,
                [],
                '{"trace_id":"trace_400","status":400,"error":"Bad Request","message":["product_id is required"],"code":"validation_failed"}',
            ),
        ]);

        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
                'max_retries' => 0,
            ],
            transport: $transport,
        );

        try {
            $client->rawRequest('POST', '/v1/checkouts', body: ['foo' => 'bar']);
            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(400, $exception->statusCode());
            $this->assertSame('trace_400', $exception->traceId());
            $this->assertSame('Bad Request', $exception->errorType());
            $this->assertSame(['product_id is required'], $exception->messages());
            $this->assertSame('validation_failed', $exception->vendorCode());
        }
    }

    public function test_it_throws_rate_limit_exception_when_retries_are_exhausted(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(429, [], '{"trace_id":"trace_a","status":429,"error":"Too Many Requests","message":["Retry later"]}'),
            new HttpResponse(429, [], '{"trace_id":"trace_b","status":429,"error":"Too Many Requests","message":["Retry later"]}'),
        ]);
        $sleeper = new FakeSleeper();

        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
                'max_retries' => 1,
                'retry_base_delay_ms' => 250,
                'retry_max_delay_ms' => 250,
            ],
            transport: $transport,
            sleeper: $sleeper,
        );

        $this->expectException(RateLimitException::class);

        try {
            $client->rawRequest('GET', '/v1/transactions', ['transaction_id' => 'txn_1']);
        } finally {
            $this->assertCount(2, $transport->requests());
            $this->assertCount(1, $sleeper->sleptMilliseconds());
        }
    }
}
