<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Request\Customers;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Request\RequestPayloadInterface;

final class CreateBillingLinkRequest implements RequestPayloadInterface
{
    public function __construct(
        private readonly string $customerId,
        private readonly ?string $requestId = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $normalizedCustomerId = trim($this->customerId);

        if ($normalizedCustomerId === '') {
            throw new InvalidConfigurationException('customerId must not be empty.');
        }

        return [
            'customer_id' => $normalizedCustomerId,
        ];
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
