<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Product;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;

enum BillingPeriod: string
{
    case ONCE = 'once';
    case EVERY_MONTH = 'every-month';
    case EVERY_THREE_MONTHS = 'every-three-months';
    case EVERY_SIX_MONTHS = 'every-six-months';
    case EVERY_YEAR = 'every-year';

    public static function toValue(self|string $billingPeriod): string
    {
        if ($billingPeriod instanceof self) {
            return $billingPeriod->value;
        }

        $normalized = strtolower(trim($billingPeriod));

        if ($normalized === '') {
            throw new InvalidConfigurationException('billingPeriod must not be empty when provided.');
        }

        return $normalized;
    }
}
