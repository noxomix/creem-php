<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Resource;

use Noxomix\CreemPhp\Subscription\SubscriptionStatus;

final class SubscriptionResource
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private readonly array $attributes,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    public function id(): ?string
    {
        return self::stringOrNull($this->attributes['id'] ?? null);
    }

    public function mode(): ?string
    {
        return self::stringOrNull($this->attributes['mode'] ?? null);
    }

    public function status(): ?SubscriptionStatus
    {
        return SubscriptionStatus::fromApiValue($this->attributes['status'] ?? null);
    }

    public function statusValue(): ?string
    {
        $status = $this->status();

        if ($status === null) {
            return null;
        }

        return $status->value;
    }

    public function customerId(): ?string
    {
        return self::idFromStringOrEntity($this->attributes['customer'] ?? null);
    }

    public function productId(): ?string
    {
        return self::idFromStringOrEntity($this->attributes['product'] ?? null);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function items(): array
    {
        $items = $this->attributes['items'] ?? null;

        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $normalized[] = $item;
            }
        }

        return $normalized;
    }

    public function collectionMethod(): ?string
    {
        return self::stringOrNull($this->attributes['collection_method'] ?? null);
    }

    public function lastTransactionId(): ?string
    {
        return self::stringOrNull($this->attributes['last_transaction_id'] ?? null);
    }

    public function nextTransactionDate(): ?string
    {
        return self::stringOrNull($this->attributes['next_transaction_date'] ?? null);
    }

    public function currentPeriodEndDate(): ?string
    {
        return self::stringOrNull($this->attributes['current_period_end_date'] ?? null);
    }

    public function canceledAt(): ?string
    {
        return self::stringOrNull($this->attributes['canceled_at'] ?? null);
    }

    private static function idFromStringOrEntity(mixed $value): ?string
    {
        if (is_string($value)) {
            return self::stringOrNull($value);
        }

        if (! is_array($value)) {
            return null;
        }

        return self::stringOrNull($value['id'] ?? null);
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }
}
