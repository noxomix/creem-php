<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Resource;

final class TransactionResource
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

    public function status(): ?string
    {
        return self::stringOrNull($this->attributes['status'] ?? null);
    }

    public function amount(): ?int
    {
        return self::intOrNull($this->attributes['amount'] ?? null);
    }

    public function amountPaid(): ?int
    {
        return self::intOrNull($this->attributes['amount_paid'] ?? null);
    }

    public function discountAmount(): ?int
    {
        return self::intOrNull($this->attributes['discount_amount'] ?? null);
    }

    public function taxAmount(): ?int
    {
        return self::intOrNull($this->attributes['tax_amount'] ?? null);
    }

    public function refundedAmount(): ?int
    {
        return self::intOrNull($this->attributes['refunded_amount'] ?? null);
    }

    public function currency(): ?string
    {
        return self::stringOrNull($this->attributes['currency'] ?? null);
    }

    public function type(): ?string
    {
        return self::stringOrNull($this->attributes['type'] ?? null);
    }

    public function orderId(): ?string
    {
        return self::idFromStringOrEntity($this->attributes['order'] ?? null);
    }

    public function subscriptionId(): ?string
    {
        return self::idFromStringOrEntity($this->attributes['subscription'] ?? null);
    }

    public function customerId(): ?string
    {
        return self::idFromStringOrEntity($this->attributes['customer'] ?? null);
    }

    public function description(): ?string
    {
        return self::stringOrNull($this->attributes['description'] ?? null);
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

    private static function intOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (! is_string($value) || ! preg_match('/^-?\d+$/', $value)) {
            return null;
        }

        return (int) $value;
    }
}
