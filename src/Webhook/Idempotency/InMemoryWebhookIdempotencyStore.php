<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Webhook\Idempotency;

final class InMemoryWebhookIdempotencyStore implements WebhookIdempotencyStoreInterface
{
    /** @var array<string, true> */
    private array $claimedEvents = [];

    /** @var array<string, true> */
    private array $processedEvents = [];

    public function claim(string $eventId): bool
    {
        if (isset($this->processedEvents[$eventId]) || isset($this->claimedEvents[$eventId])) {
            return false;
        }

        $this->claimedEvents[$eventId] = true;

        return true;
    }

    public function markProcessed(string $eventId): void
    {
        unset($this->claimedEvents[$eventId]);
        $this->processedEvents[$eventId] = true;
    }

    public function release(string $eventId): void
    {
        unset($this->claimedEvents[$eventId]);
    }
}
