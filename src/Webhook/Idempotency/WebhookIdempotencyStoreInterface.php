<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Webhook\Idempotency;

interface WebhookIdempotencyStoreInterface
{
    /**
     * Atomically claim an event ID for processing.
     *
     * Returns false when the event was already claimed or processed.
     */
    public function claim(string $eventId): bool;

    public function markProcessed(string $eventId): void;

    /**
     * Release a previously claimed event ID after a processing failure.
     */
    public function release(string $eventId): void;
}
