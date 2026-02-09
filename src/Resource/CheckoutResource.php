<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Resource;

final class CheckoutResource
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

    public function checkoutUrl(): ?string
    {
        return self::stringOrNull($this->attributes['checkout_url'] ?? null);
    }

    public function productId(): ?string
    {
        return self::idFromStringOrEntity($this->attributes['product'] ?? null);
    }

    public function customerId(): ?string
    {
        return self::idFromStringOrEntity($this->attributes['customer'] ?? null);
    }

    public function subscriptionId(): ?string
    {
        return self::idFromStringOrEntity($this->attributes['subscription'] ?? null);
    }

    public function orderId(): ?string
    {
        return self::idFromStringOrEntity($this->attributes['order'] ?? null);
    }

    public function units(): ?int
    {
        return self::intOrNull($this->attributes['units'] ?? null);
    }

    public function successUrl(): ?string
    {
        return self::stringOrNull($this->attributes['success_url'] ?? null);
    }

    public function requestId(): ?string
    {
        return self::stringOrNull($this->attributes['request_id'] ?? null);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function metadata(): ?array
    {
        $value = $this->attributes['metadata'] ?? null;

        if (! is_array($value)) {
            return null;
        }

        return $value;
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
