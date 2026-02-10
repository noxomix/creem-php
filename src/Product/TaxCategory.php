<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Product;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;

enum TaxCategory: string
{
    case SAAS = 'saas';
    case DIGITAL_GOODS_SERVICE = 'digital-goods-service';
    case EBOOKS = 'ebooks';

    public static function fromInput(self|string $taxCategory): self
    {
        if ($taxCategory instanceof self) {
            return $taxCategory;
        }

        $normalized = strtolower(trim($taxCategory));
        $resolved = self::tryFrom($normalized);

        if ($resolved === null) {
            throw new InvalidConfigurationException('taxCategory must be "saas", "digital-goods-service", or "ebooks".');
        }

        return $resolved;
    }
}
