<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Subscription;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case TRIALING = 'trialing';
    case PAUSED = 'paused';
    case SCHEDULED_CANCEL = 'scheduled_cancel';
    case UNPAID = 'unpaid';
    case PAST_DUE = 'past_due';
    case EXPIRED = 'expired';
    case CANCELED = 'canceled';

    public static function fromApiValue(mixed $value): ?self
    {
        if (! is_string($value)) {
            return null;
        }

        return self::tryFrom(strtolower(trim($value)));
    }
}
