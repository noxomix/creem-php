<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Subscription;

enum SubscriptionEventType: string
{
    case CHECKOUT_COMPLETED = 'checkout.completed';
    case SUBSCRIPTION_PAID = 'subscription.paid';
    case SUBSCRIPTION_CANCELED = 'subscription.canceled';
    case SUBSCRIPTION_ACTIVE = 'subscription.active';
    case SUBSCRIPTION_UPDATE = 'subscription.update';
    case SUBSCRIPTION_TRIALING = 'subscription.trialing';
    case SUBSCRIPTION_PAUSED = 'subscription.paused';
    case SUBSCRIPTION_SCHEDULED_CANCEL = 'subscription.scheduled_cancel';
    case SUBSCRIPTION_PAST_DUE = 'subscription.past_due';
    case SUBSCRIPTION_EXPIRED = 'subscription.expired';

    public static function fromApiValue(mixed $value): ?self
    {
        if (! is_string($value)) {
            return null;
        }

        return self::tryFrom(strtolower(trim($value)));
    }
}
