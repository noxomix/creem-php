<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Webhook;

enum WebhookEventType: string
{
    case CHECKOUT_COMPLETED = 'checkout.completed';
    case SUBSCRIPTION_ACTIVE = 'subscription.active';
    case SUBSCRIPTION_UPDATE = 'subscription.update';
    case SUBSCRIPTION_TRIALING = 'subscription.trialing';
    case SUBSCRIPTION_PAUSED = 'subscription.paused';
    case SUBSCRIPTION_SCHEDULED_CANCEL = 'subscription.scheduled_cancel';
    case SUBSCRIPTION_PAST_DUE = 'subscription.past_due';
    case SUBSCRIPTION_EXPIRED = 'subscription.expired';
    case SUBSCRIPTION_PAID = 'subscription.paid';
    case SUBSCRIPTION_CANCELED = 'subscription.canceled';
    case REFUND_CREATED = 'refund.created';
    case DISPUTE_CREATED = 'dispute.created';

    public static function fromApiValue(string $value): ?self
    {
        return self::tryFrom(strtolower(trim($value)));
    }
}
