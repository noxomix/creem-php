<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Request\Subscriptions;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Request\RequestPayloadInterface;

final class UpdateSubscriptionRequest implements RequestPayloadInterface
{
    /** @var array<int, string> */
    private const ALLOWED_UPDATE_BEHAVIORS = [
        'proration-charge-immediately',
        'proration-charge',
        'proration-none',
    ];

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly array $payload,
        private readonly ?string $requestId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $extra
     */
    public static function withUpdateBehavior(string $updateBehavior, array $extra = [], ?string $requestId = null): self
    {
        $normalizedUpdateBehavior = self::normalizeUpdateBehavior($updateBehavior);

        return new self(
            payload: array_merge($extra, ['update_behavior' => $normalizedUpdateBehavior]),
            requestId: $requestId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }

    public function requestId(): ?string
    {
        $normalized = $this->requestId === null ? null : trim($this->requestId);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }

    private static function normalizeUpdateBehavior(string $updateBehavior): string
    {
        $normalized = strtolower(trim($updateBehavior));

        if (! in_array($normalized, self::ALLOWED_UPDATE_BEHAVIORS, true)) {
            throw new InvalidConfigurationException(sprintf(
                'updateBehavior must be one of: %s.',
                implode(', ', self::ALLOWED_UPDATE_BEHAVIORS),
            ));
        }

        return $normalized;
    }
}
