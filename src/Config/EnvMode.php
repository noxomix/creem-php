<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Config;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;

enum EnvMode: string
{
    case PROD = 'prod';
    case TEST = 'test';
    case SANDBOX = 'sandbox';

    public static function fromInput(self|string $mode): self
    {
        if ($mode instanceof self) {
            return $mode;
        }

        $normalized = strtolower(trim($mode));

        if ($normalized === 'production') {
            return self::PROD;
        }

        $resolved = self::tryFrom($normalized);

        if ($resolved === null) {
            throw new InvalidConfigurationException(sprintf(
                'Unsupported Creem mode "%s". Allowed values: prod, test, sandbox.',
                $mode,
            ));
        }

        return $resolved;
    }

    public function defaultBaseUrl(): string
    {
        return $this === self::PROD
            ? 'https://api.creem.io'
            : 'https://test-api.creem.io';
    }
}
