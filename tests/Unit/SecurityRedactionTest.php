<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Unit;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Exception\InvalidWebhookSignatureException;
use Noxomix\CreemPhp\Exception\NetworkException;
use Noxomix\CreemPhp\Exception\WebhookDispatchException;
use Noxomix\CreemPhp\Http\GuzzleTransport;
use Noxomix\CreemPhp\Http\HttpRequest;
use Noxomix\CreemPhp\Http\HttpResponse;
use Noxomix\CreemPhp\Tests\Support\FakeSleeper;
use Noxomix\CreemPhp\Tests\Support\FakeTransport;
use Noxomix\CreemPhp\Tests\Support\InMemoryLogger;
use Noxomix\CreemPhp\Webhook\Dispatch\DefaultWebhookDispatcher;
use Noxomix\CreemPhp\Webhook\WebhookProcessor;
use Noxomix\CreemPhp\Webhook\WebhookSignatureVerifier;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request as PsrRequest;
use PHPUnit\Framework\TestCase;

final class SecurityRedactionTest extends TestCase
{
    public function test_client_logs_never_include_api_key_or_auth_header_values(): void
    {
        $apiKey = 'creem_secret_key_for_logging_test';
        $logger = new InMemoryLogger();
        $sleeper = new FakeSleeper();
        $transport = new FakeTransport([
            new HttpResponse(429, [], '{"trace_id":"trace_1","status":429,"error":"rate_limit","message":["retry"]}'),
            new HttpResponse(200, [], '{"ok":true}'),
        ]);

        $client = new CreemClient(
            config: [
                'api_key' => $apiKey,
                'mode' => 'test',
                'max_retries' => 1,
                'retry_base_delay_ms' => 100,
                'retry_max_delay_ms' => 100,
            ],
            transport: $transport,
            logger: $logger,
            sleeper: $sleeper,
        );

        $client->rawRequest('GET', '/v1/transactions');

        $this->assertFalse($logger->contains($apiKey));
        $this->assertFalse($logger->contains('x-api-key'));
    }

    public function test_guzzle_transport_redacts_api_key_from_network_logs_and_exceptions(): void
    {
        $apiKey = 'creem_super_secret_key';
        $logger = new InMemoryLogger();
        $guzzleRequest = new PsrRequest('GET', '/v1/customers');
        $mock = new MockHandler([
            new ConnectException(
                sprintf('connection failed with x-api-key=%s', $apiKey),
                $guzzleRequest,
            ),
        ]);
        $client = new Client([
            'handler' => HandlerStack::create($mock),
            'base_uri' => 'https://test-api.creem.io',
        ]);
        $transport = new GuzzleTransport(
            baseUrl: 'https://test-api.creem.io',
            client: $client,
            logger: $logger,
        );

        try {
            $transport->send(new HttpRequest(
                method: 'GET',
                path: '/v1/customers',
                headers: [
                    'x-api-key' => $apiKey,
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                ],
                query: [],
                body: null,
                connectTimeoutSeconds: 10.0,
                requestTimeoutSeconds: 30.0,
            ));
            $this->fail('Expected NetworkException was not thrown.');
        } catch (NetworkException $exception) {
            $this->assertFalse(str_contains($exception->getMessage(), $apiKey));
            $this->assertTrue(str_contains($exception->getMessage(), '[REDACTED]'));
        }

        $this->assertFalse($logger->contains($apiKey));
        $this->assertFalse($logger->contains('x-api-key: '.$apiKey));
    }

    public function test_webhook_exceptions_do_not_leak_webhook_secret(): void
    {
        $secret = 'whsec_sensitive_secret';
        $payload = $this->fixture('checkout.completed.json');
        $verifier = new WebhookSignatureVerifier();
        $signature = $verifier->generateSignature($payload, $secret);

        $processorWithInvalidSignature = new WebhookProcessor(
            webhookSecret: $secret,
            dispatcher: new DefaultWebhookDispatcher(),
        );

        try {
            $processorWithInvalidSignature->process($payload, 'invalid');
            $this->fail('Expected InvalidWebhookSignatureException was not thrown.');
        } catch (InvalidWebhookSignatureException $exception) {
            $this->assertFalse(str_contains($exception->getMessage(), $secret));
        }

        $processorWithDispatchFailure = new WebhookProcessor(
            webhookSecret: $secret,
            dispatcher: new DefaultWebhookDispatcher([
                'checkout.completed' => static function () use ($secret): void {
                    throw new \RuntimeException('handler failed with secret '.$secret);
                },
            ]),
        );

        try {
            $processorWithDispatchFailure->process($payload, $signature);
            $this->fail('Expected WebhookDispatchException was not thrown.');
        } catch (WebhookDispatchException $exception) {
            $this->assertFalse(str_contains($exception->getMessage(), $secret));
        }
    }

    private function fixture(string $file): string
    {
        $path = __DIR__.'/../Fixtures/webhooks/'.$file;
        $contents = file_get_contents($path);

        if ($contents === false) {
            $this->fail(sprintf('Failed to load fixture "%s".', $path));
        }

        return $contents;
    }
}
