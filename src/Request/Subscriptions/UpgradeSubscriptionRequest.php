<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Request\Subscriptions;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Request\RequestPayloadInterface;

final class UpgradeSubscriptionRequest implements RequestPayloadInterface
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        private readonly string $productId,
        private readonly ?string $requestId = null,
        private readonly array $extra = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $normalizedProductId = trim($this->productId);

        if ($normalizedProductId === '') {
            throw new InvalidConfigurationException('productId must not be empty.');
        }

        return array_merge(
            $this->extra,
            ['product_id' => $normalizedProductId],
        );
    }

    public function requestId(): ?string
    {
        $normalized = $this->requestId === null ? null : trim($this->requestId);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }
}
