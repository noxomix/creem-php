<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Request\Checkouts;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Request\RequestPayloadInterface;

final class CreateCheckoutRequest implements RequestPayloadInterface
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        private readonly string $productId,
        private readonly ?string $successUrl = null,
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

        $payload = array_merge(
            $this->extra,
            [
                'product_id' => $normalizedProductId,
            ],
        );

        if ($this->successUrl === null) {
            return $payload;
        }

        $normalizedSuccessUrl = trim($this->successUrl);

        if ($normalizedSuccessUrl === '') {
            throw new InvalidConfigurationException('successUrl must not be empty when provided.');
        }

        $payload['success_url'] = $normalizedSuccessUrl;

        return $payload;
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
