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

    public function description(): ?string
    {
        return self::stringOrNull($this->attributes['description'] ?? null);
    }

    public function imageUrl(): ?string
    {
        return self::stringOrNull($this->attributes['image_url'] ?? null);
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

    public function taxMode(): ?string
    {
        return self::stringOrNull($this->attributes['tax_mode'] ?? null);
    }

    public function taxCategory(): ?string
    {
        return self::stringOrNull($this->attributes['tax_category'] ?? null);
    }

    public function defaultSuccessUrl(): ?string
    {
        return self::stringOrNull($this->attributes['default_success_url'] ?? null);
    }

    public function abandonedCartRecoveryEnabled(): ?bool
    {
        return self::boolOrNull($this->attributes['abandoned_cart_recovery_enabled'] ?? null);
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

    private static function boolOrNull(mixed $value): ?bool
    {
        if (! is_bool($value)) {
            return null;
        }

        return $value;
    }
}
