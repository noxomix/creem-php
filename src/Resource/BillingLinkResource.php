<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Resource;

final class BillingLinkResource
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

    public function url(): ?string
    {
        $value = $this->attributes['url'] ?? $this->attributes['customer_portal_link'] ?? null;

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
