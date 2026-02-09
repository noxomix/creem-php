<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Resource;

final class CustomerResource
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

    public function email(): ?string
    {
        return self::stringOrNull($this->attributes['email'] ?? null);
    }

    public function name(): ?string
    {
        return self::stringOrNull($this->attributes['name'] ?? null);
    }

    public function country(): ?string
    {
        return self::stringOrNull($this->attributes['country'] ?? null);
    }

    public function createdAt(): ?string
    {
        return self::stringOrNull($this->attributes['created_at'] ?? null);
    }

    public function updatedAt(): ?string
    {
        return self::stringOrNull($this->attributes['updated_at'] ?? null);
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
