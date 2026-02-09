<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Resource;

final class RefundResource
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
        $value = $this->attributes['id'] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }

    public function status(): ?string
    {
        $value = $this->attributes['status'] ?? null;

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
