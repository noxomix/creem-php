<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Resource;

final class LicenseResource
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

    public function status(): ?string
    {
        return self::stringOrNull($this->attributes['status'] ?? null);
    }

    public function key(): ?string
    {
        return self::stringOrNull($this->attributes['key'] ?? null);
    }

    public function activation(): ?int
    {
        return self::intOrNull($this->attributes['activation'] ?? null);
    }

    public function activationLimit(): ?int
    {
        return self::intOrNull($this->attributes['activation_limit'] ?? null);
    }

    public function expiresAt(): ?string
    {
        return self::stringOrNull($this->attributes['expires_at'] ?? null);
    }

    public function instanceId(): ?string
    {
        $instance = $this->instanceAttributes();

        return self::stringOrNull($instance['id'] ?? null);
    }

    public function instanceStatus(): ?string
    {
        $instance = $this->instanceAttributes();

        return self::stringOrNull($instance['status'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function instanceAttributes(): array
    {
        $instance = $this->attributes['instance'] ?? null;

        if (! is_array($instance)) {
            return [];
        }

        return $instance;
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
