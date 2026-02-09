<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Request\Subscriptions;

use Noxomix\CreemPhp\Request\RequestPayloadInterface;

final class PauseSubscriptionRequest implements RequestPayloadInterface
{
    public function __construct(
        private readonly ?string $requestId = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [];
    }

    public function requestId(): ?string
    {
        if ($this->requestId === null) {
            return null;
        }

        $normalized = trim($this->requestId);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }
}
