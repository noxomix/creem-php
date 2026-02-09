<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Resource;

final class DiscountResource
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

    public function code(): ?string
    {
        return self::stringOrNull($this->attributes['code'] ?? null);
    }

    public function status(): ?string
    {
        return self::stringOrNull($this->attributes['status'] ?? null);
    }

    public function type(): ?string
    {
        return self::stringOrNull($this->attributes['type'] ?? null);
    }

    public function duration(): ?string
    {
        return self::stringOrNull($this->attributes['duration'] ?? null);
    }

    public function redeemCount(): ?int
    {
        return self::intOrNull($this->attributes['redeem_count'] ?? null);
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
