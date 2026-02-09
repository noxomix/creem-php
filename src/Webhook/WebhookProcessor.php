<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Webhook;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Exception\InvalidWebhookSignatureException;
use Noxomix\CreemPhp\Exception\WebhookDispatchException;
use Noxomix\CreemPhp\Webhook\Dispatch\DefaultWebhookDispatcher;
use Noxomix\CreemPhp\Webhook\Dispatch\WebhookEventDispatcherInterface;
use Noxomix\CreemPhp\Webhook\Idempotency\InMemoryWebhookIdempotencyStore;
use Noxomix\CreemPhp\Webhook\Idempotency\WebhookIdempotencyStoreInterface;
use Throwable;

final class WebhookProcessor
{
    private readonly WebhookSignatureVerifier $signatureVerifier;
    private readonly WebhookEventParser $eventParser;
    private readonly WebhookEventDispatcherInterface $dispatcher;
    private readonly WebhookIdempotencyStoreInterface $idempotencyStore;

    public function __construct(
        private readonly string $webhookSecret,
        ?WebhookSignatureVerifier $signatureVerifier = null,
        ?WebhookEventParser $eventParser = null,
        ?WebhookEventDispatcherInterface $dispatcher = null,
        ?WebhookIdempotencyStoreInterface $idempotencyStore = null,
    ) {
        if (trim($this->webhookSecret) === '') {
            throw new InvalidConfigurationException('webhookSecret must not be empty.');
        }

        $this->signatureVerifier = $signatureVerifier ?? new WebhookSignatureVerifier();
        $this->eventParser = $eventParser ?? new WebhookEventParser();
        $this->dispatcher = $dispatcher ?? new DefaultWebhookDispatcher();
        $this->idempotencyStore = $idempotencyStore ?? new InMemoryWebhookIdempotencyStore();
    }

    public function process(string $rawPayload, ?string $providedSignature): WebhookProcessResult
    {
        if (! $this->signatureVerifier->verify($rawPayload, $providedSignature, $this->webhookSecret)) {
            throw new InvalidWebhookSignatureException('Invalid webhook signature.');
        }

        $event = $this->eventParser->parse($rawPayload);

        if (! $this->idempotencyStore->claim($event->id())) {
            return WebhookProcessResult::duplicate($event);
        }

        try {
            $handled = $this->dispatcher->dispatch($event);
        } catch (Throwable $throwable) {
            $this->idempotencyStore->release($event->id());
            throw WebhookDispatchException::forEvent($event->id(), $throwable);
        }

        $this->idempotencyStore->markProcessed($event->id());

        if (! $handled) {
            return WebhookProcessResult::ignored($event);
        }

        return WebhookProcessResult::processed($event);
    }
}
