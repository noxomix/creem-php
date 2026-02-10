<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Discount;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;

enum DiscountDuration: string
{
    case FOREVER = 'forever';
    case ONCE = 'once';
    case REPEATING = 'repeating';

    public static function fromInput(self|string $duration): self
    {
        if ($duration instanceof self) {
            return $duration;
        }

        $normalized = strtolower(trim($duration));
        $resolved = self::tryFrom($normalized);

        if ($resolved === null) {
            throw new InvalidConfigurationException('duration must be "forever", "once", or "repeating".');
        }

        return $resolved;
    }
}
