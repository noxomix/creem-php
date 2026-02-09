<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Resource;

final class ProductResource
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

    public function name(): ?string
    {
        return self::stringOrNull($this->attributes['name'] ?? null);
    }

    public function status(): ?string
    {
        return self::stringOrNull($this->attributes['status'] ?? null);
    }

    public function price(): ?int
    {
        return self::intOrNull($this->attributes['price'] ?? null);
    }

    public function currency(): ?string
    {
        return self::stringOrNull($this->attributes['currency'] ?? null);
    }

    public function billingType(): ?string
    {
        return self::stringOrNull($this->attributes['billing_type'] ?? null);
    }

    public function billingPeriod(): ?string
    {
        return self::stringOrNull($this->attributes['billing_period'] ?? null);
    }

    private static function intOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '' && ctype_digit($value)) {
            return (int) $value;
        }

        return null;
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
