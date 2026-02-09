<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Webhook;

use Noxomix\CreemPhp\Resource\CheckoutResource;
use Noxomix\CreemPhp\Resource\DisputeResource;
use Noxomix\CreemPhp\Resource\RefundResource;
use Noxomix\CreemPhp\Resource\SubscriptionResource;

final class WebhookEvent
{
    /**
     * @param array<string, mixed> $object
     */
    public function __construct(
        private readonly string $id,
        private readonly string $eventType,
        private readonly ?int $createdAt,
        private readonly array $object,
        private readonly string $rawPayload,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function createdAt(): ?int
    {
        return $this->createdAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function object(): array
    {
        return $this->object;
    }

    public function rawPayload(): string
    {
        return $this->rawPayload;
    }

    public function type(): ?WebhookEventType
    {
        return WebhookEventType::fromApiValue($this->eventType);
    }

    public function isType(WebhookEventType $type): bool
    {
        return $this->type() === $type;
    }

    public function isSubscriptionEvent(): bool
    {
        $eventType = strtolower(trim($this->eventType));

        return str_starts_with($eventType, 'subscription.');
    }

    public function checkout(): ?CheckoutResource
    {
        if (! $this->isType(WebhookEventType::CHECKOUT_COMPLETED)) {
            return null;
        }

        return new CheckoutResource($this->object);
    }

    public function subscription(): ?SubscriptionResource
    {
        if ($this->isSubscriptionEvent()) {
            return new SubscriptionResource($this->object);
        }

        if ($this->isType(WebhookEventType::CHECKOUT_COMPLETED)) {
            $subscription = $this->object['subscription'] ?? null;

            if (is_array($subscription)) {
                return new SubscriptionResource($subscription);
            }
        }

        return null;
    }

    public function refund(): ?RefundResource
    {
        if (! $this->isType(WebhookEventType::REFUND_CREATED)) {
            return null;
        }

        return new RefundResource($this->object);
    }

    public function dispute(): ?DisputeResource
    {
        if (! $this->isType(WebhookEventType::DISPUTE_CREATED)) {
            return null;
        }

        return new DisputeResource($this->object);
    }
}
