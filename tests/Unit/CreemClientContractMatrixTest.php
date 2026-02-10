<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Unit;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Exception\ApiException;
use Noxomix\CreemPhp\Exception\AuthenticationException;
use Noxomix\CreemPhp\Exception\ConflictException;
use Noxomix\CreemPhp\Exception\NetworkException;
use Noxomix\CreemPhp\Exception\NotFoundException;
use Noxomix\CreemPhp\Exception\RateLimitException;
use Noxomix\CreemPhp\Exception\ServerException;
use Noxomix\CreemPhp\Exception\ValidationException;
use Noxomix\CreemPhp\Http\HttpResponse;
use Noxomix\CreemPhp\Tests\Support\FakeSleeper;
use Noxomix\CreemPhp\Tests\Support\FakeTransport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CreemClientContractMatrixTest extends TestCase
{
    #[DataProvider('retryableStatusProvider')]
    public function test_retry_matrix_retries_retryable_status_codes(int $status): void
    {
        $transport = new FakeTransport([
            new HttpResponse(
                $status,
                [],
                sprintf(
                    '{"trace_id":"trace_retry_%d","status":%d,"error":"temporary","message":["try again"],"code":"retry_case"}',
                    $status,
                    $status,
                ),
            ),
            new HttpResponse(200, [], '{"ok":true}'),
        ]);
        $sleeper = new FakeSleeper();

        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
                'max_retries' => 1,
                'retry_base_delay_ms' => 100,
                'retry_max_delay_ms' => 100,
            ],
            transport: $transport,
            sleeper: $sleeper,
        );

        $response = $client->rawRequest('GET', '/v1/transactions');

        $this->assertSame(['ok' => true], $response);
        $this->assertCount(2, $transport->requests());
        $this->assertCount(1, $sleeper->sleptMilliseconds());
    }

    public function test_retry_matrix_retries_network_failures(): void
    {
        $transport = new FakeTransport([
            NetworkException::fromThrowable(new \RuntimeException('socket reset')),
            new HttpResponse(200, [], '{"ok":true}'),
        ]);
        $sleeper = new FakeSleeper();

        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
                'max_retries' => 1,
                'retry_base_delay_ms' => 100,
                'retry_max_delay_ms' => 100,
            ],
            transport: $transport,
            sleeper: $sleeper,
        );

        $response = $client->rawRequest('GET', '/v1/transactions');

        $this->assertSame(['ok' => true], $response);
        $this->assertCount(2, $transport->requests());
        $this->assertCount(1, $sleeper->sleptMilliseconds());
    }

    /**
     * @return iterable<string, array{status:int,exceptionClass:class-string<ApiException>}>
     */
    public static function errorMappingProvider(): iterable
    {
        yield '400-validation' => [
            'status' => 400,
            'exceptionClass' => ValidationException::class,
        ];
        yield '401-authentication' => [
            'status' => 401,
            'exceptionClass' => AuthenticationException::class,
        ];
        yield '403-authentication' => [
            'status' => 403,
            'exceptionClass' => AuthenticationException::class,
        ];
        yield '404-not-found' => [
            'status' => 404,
            'exceptionClass' => NotFoundException::class,
        ];
        yield '409-conflict' => [
            'status' => 409,
            'exceptionClass' => ConflictException::class,
        ];
        yield '429-rate-limit' => [
            'status' => 429,
            'exceptionClass' => RateLimitException::class,
        ];
        yield '500-server' => [
            'status' => 500,
            'exceptionClass' => ServerException::class,
        ];
    }

    #[DataProvider('errorMappingProvider')]
    public function test_error_mapping_matrix_maps_status_to_typed_exceptions(
        int $status,
        string $exceptionClass,
    ): void {
        $transport = new FakeTransport([
            new HttpResponse(
                $status,
                [],
                sprintf(
                    '{"trace_id":"trace_%d","status":%d,"error":"error_%d","message":["message_%d"],"code":"code_%d"}',
                    $status,
                    $status,
                    $status,
                    $status,
                    $status,
                ),
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
            $client->rawRequest('GET', '/v1/transactions');
            $this->fail('Expected API exception was not thrown.');
        } catch (ApiException $exception) {
            $this->assertInstanceOf($exceptionClass, $exception);
            $this->assertSame($status, $exception->statusCode());
            $this->assertSame(sprintf('trace_%d', $status), $exception->traceId());
            $this->assertSame(sprintf('error_%d', $status), $exception->errorType());
            $this->assertSame([sprintf('message_%d', $status)], $exception->messages());
            $this->assertSame(sprintf('code_%d', $status), $exception->vendorCode());
        }
    }

    /**
     * @return iterable<string, array{status:int}>
     */
    public static function retryableStatusProvider(): iterable
    {
        yield '429-rate-limit' => ['status' => 429];
        yield '500-server-error' => ['status' => 500];
        yield '502-bad-gateway' => ['status' => 502];
        yield '503-service-unavailable' => ['status' => 503];
        yield '504-gateway-timeout' => ['status' => 504];
    }
}
