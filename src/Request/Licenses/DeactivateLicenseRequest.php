<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Request\Licenses;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Request\RequestPayloadInterface;

final class DeactivateLicenseRequest implements RequestPayloadInterface
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        private readonly string $key,
        private readonly string $instanceId,
        private readonly ?string $requestId = null,
        private readonly array $extra = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $normalizedKey = trim($this->key);
        $normalizedInstanceId = trim($this->instanceId);

        if ($normalizedKey === '') {
            throw new InvalidConfigurationException('key must not be empty.');
        }

        if ($normalizedInstanceId === '') {
            throw new InvalidConfigurationException('instanceId must not be empty.');
        }

        return array_merge(
            $this->extra,
            [
                'key' => $normalizedKey,
                'instance_id' => $normalizedInstanceId,
            ],
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
