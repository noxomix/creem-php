<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Product;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;

enum BillingType: string
{
    case RECURRING = 'recurring';
    case ONETIME = 'onetime';

    public static function fromInput(self|string $billingType): self
    {
        if ($billingType instanceof self) {
            return $billingType;
        }

        $normalized = strtolower(trim($billingType));
        $resolved = self::tryFrom($normalized);

        if ($resolved === null) {
            throw new InvalidConfigurationException('billingType must be "recurring" or "onetime".');
        }

        return $resolved;
    }
}
