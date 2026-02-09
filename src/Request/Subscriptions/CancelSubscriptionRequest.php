<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Request\Subscriptions;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Request\RequestPayloadInterface;

final class CancelSubscriptionRequest implements RequestPayloadInterface
{
    public function __construct(
        private readonly ?string $mode = null,
        private readonly ?string $onExecute = null,
        private readonly ?string $requestId = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [];
        $normalizedMode = $this->normalizeNullable($this->mode);
        $normalizedOnExecute = $this->normalizeNullable($this->onExecute);

        if ($normalizedMode !== null) {
            if (! in_array($normalizedMode, ['immediate', 'scheduled'], true)) {
                throw new InvalidConfigurationException('mode must be "immediate" or "scheduled" when provided.');
            }

            $payload['mode'] = $normalizedMode;
        }

        if ($normalizedOnExecute !== null) {
            if (! in_array($normalizedOnExecute, ['cancel', 'pause'], true)) {
                throw new InvalidConfigurationException('onExecute must be "cancel" or "pause" when provided.');
            }

            if ($normalizedMode !== 'scheduled') {
                throw new InvalidConfigurationException('onExecute is only valid when mode is "scheduled".');
            }

            $payload['onExecute'] = $normalizedOnExecute;
        }

        return $payload;
    }

    public function requestId(): ?string
    {
        return $this->normalizeNullable($this->requestId);
    }

    private function normalizeNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }
}
