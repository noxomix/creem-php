<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Product;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;

enum TaxMode: string
{
    case INCLUSIVE = 'inclusive';
    case EXCLUSIVE = 'exclusive';

    public static function fromInput(self|string $taxMode): self
    {
        if ($taxMode instanceof self) {
            return $taxMode;
        }

        $normalized = strtolower(trim($taxMode));
        $resolved = self::tryFrom($normalized);

        if ($resolved === null) {
            throw new InvalidConfigurationException('taxMode must be "inclusive" or "exclusive".');
        }

        return $resolved;
    }
}
