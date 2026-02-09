<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Unit;

use Noxomix\CreemPhp\Exception\InvalidWebhookSignatureException;
use Noxomix\CreemPhp\Exception\WebhookDispatchException;
use Noxomix\CreemPhp\Webhook\Dispatch\DefaultWebhookDispatcher;
use Noxomix\CreemPhp\Webhook\WebhookProcessor;
use Noxomix\CreemPhp\Webhook\WebhookSignatureVerifier;
use PHPUnit\Framework\TestCase;

final class WebhookProcessorTest extends TestCase
{
    private const SECRET = 'whsec_test_secret';

    public function test_it_processes_valid_event_and_calls_registered_handler(): void
    {
        $payload = $this->fixture('subscription.paid.json');
        $verifier = new WebhookSignatureVerifier();
        $signature = $verifier->generateSignature($payload, self::SECRET);

        $handled = false;

        $processor = new WebhookProcessor(
            webhookSecret: self::SECRET,
            dispatcher: new DefaultWebhookDispatcher([
                'subscription.paid' => static function () use (&$handled): void {
                    $handled = true;
                },
            ]),
        );

        $result = $processor->process($payload, $signature);

        $this->assertTrue($handled);
        $this->assertTrue($result->isProcessed());
        $this->assertSame('evt_subscription_paid_1', $result->event()->id());
    }

    public function test_it_returns_duplicate_for_second_delivery_of_same_event(): void
    {
        $payload = $this->fixture('checkout.completed.json');
        $verifier = new WebhookSignatureVerifier();
        $signature = $verifier->generateSignature($payload, self::SECRET);
        $dispatchCount = 0;

        $processor = new WebhookProcessor(
            webhookSecret: self::SECRET,
            dispatcher: new DefaultWebhookDispatcher([
                'checkout.completed' => static function () use (&$dispatchCount): void {
                    $dispatchCount++;
                },
            ]),
        );

        $first = $processor->process($payload, $signature);
        $second = $processor->process($payload, $signature);

        $this->assertTrue($first->isProcessed());
        $this->assertTrue($second->isDuplicate());
        $this->assertSame(1, $dispatchCount);
    }

    public function test_it_returns_ignored_when_no_handler_is_registered(): void
    {
        $payload = $this->fixture('refund.created.json');
        $verifier = new WebhookSignatureVerifier();
        $signature = $verifier->generateSignature($payload, self::SECRET);

        $processor = new WebhookProcessor(
            webhookSecret: self::SECRET,
            dispatcher: new DefaultWebhookDispatcher(),
        );

        $result = $processor->process($payload, $signature);

        $this->assertTrue($result->isIgnored());
    }

    public function test_it_throws_for_invalid_signature(): void
    {
        $payload = $this->fixture('checkout.completed.json');

        $processor = new WebhookProcessor(
            webhookSecret: self::SECRET,
            dispatcher: new DefaultWebhookDispatcher(),
        );

        $this->expectException(InvalidWebhookSignatureException::class);

        $processor->process($payload, 'invalid');
    }

    public function test_it_wraps_dispatch_errors(): void
    {
        $payload = $this->fixture('checkout.completed.json');
        $verifier = new WebhookSignatureVerifier();
        $signature = $verifier->generateSignature($payload, self::SECRET);

        $processor = new WebhookProcessor(
            webhookSecret: self::SECRET,
            dispatcher: new DefaultWebhookDispatcher([
                'checkout.completed' => static function (): void {
                    throw new \RuntimeException('boom');
                },
            ]),
        );

        $this->expectException(WebhookDispatchException::class);

        try {
            $processor->process($payload, $signature);
        } catch (WebhookDispatchException $exception) {
            $this->assertSame('evt_checkout_1', $exception->eventId());
            throw $exception;
        }
    }

    public function test_it_allows_retry_after_dispatch_failure(): void
    {
        $payload = $this->fixture('checkout.completed.json');
        $verifier = new WebhookSignatureVerifier();
        $signature = $verifier->generateSignature($payload, self::SECRET);
        $attempts = 0;

        $processor = new WebhookProcessor(
            webhookSecret: self::SECRET,
            dispatcher: new DefaultWebhookDispatcher([
                'checkout.completed' => static function () use (&$attempts): void {
                    $attempts++;

                    if ($attempts === 1) {
                        throw new \RuntimeException('temporary failure');
                    }
                },
            ]),
        );

        try {
            $processor->process($payload, $signature);
            $this->fail('Expected WebhookDispatchException was not thrown.');
        } catch (WebhookDispatchException) {
            // Expected on first delivery.
        }

        $secondAttempt = $processor->process($payload, $signature);

        $this->assertTrue($secondAttempt->isProcessed());
        $this->assertSame(2, $attempts);
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
