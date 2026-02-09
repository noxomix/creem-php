<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Subscription;

final class SubscriptionLifecycle
{
    /** @var array<int, string> */
    private const TERMINAL_EVENTS = [
        SubscriptionEventType::SUBSCRIPTION_CANCELED->value,
    ];

    /** @var array<int, string> */
    private const NON_TERMINAL_EVENTS = [
        SubscriptionEventType::CHECKOUT_COMPLETED->value,
        SubscriptionEventType::SUBSCRIPTION_PAID->value,
        SubscriptionEventType::SUBSCRIPTION_ACTIVE->value,
        SubscriptionEventType::SUBSCRIPTION_UPDATE->value,
        SubscriptionEventType::SUBSCRIPTION_TRIALING->value,
        SubscriptionEventType::SUBSCRIPTION_PAUSED->value,
        SubscriptionEventType::SUBSCRIPTION_SCHEDULED_CANCEL->value,
        SubscriptionEventType::SUBSCRIPTION_PAST_DUE->value,
        SubscriptionEventType::SUBSCRIPTION_EXPIRED->value,
    ];

    /** @var array<int, string> */
    private const RETRY_WINDOW_STATUSES = [
        SubscriptionStatus::UNPAID->value,
        SubscriptionStatus::PAST_DUE->value,
        SubscriptionStatus::EXPIRED->value,
    ];

    /** @var array<int, string> */
    private const TRANSITIONAL_STATUSES = [
        SubscriptionStatus::SCHEDULED_CANCEL->value,
        SubscriptionStatus::TRIALING->value,
        SubscriptionStatus::PAUSED->value,
    ];

    /**
     * @return array<int, string>
     */
    public static function terminalEvents(): array
    {
        return self::TERMINAL_EVENTS;
    }

    /**
     * @return array<int, string>
     */
    public static function nonTerminalEvents(): array
    {
        return self::NON_TERMINAL_EVENTS;
    }

    /**
     * @return array<int, string>
     */
    public static function retryWindowStatuses(): array
    {
        return self::RETRY_WINDOW_STATUSES;
    }

    /**
     * @return array<int, string>
     */
    public static function transitionalStatuses(): array
    {
        return self::TRANSITIONAL_STATUSES;
    }

    public static function isTerminalEvent(string $event): bool
    {
        $normalized = strtolower(trim($event));

        return in_array($normalized, self::TERMINAL_EVENTS, true);
    }

    public static function isNonTerminalEvent(string $event): bool
    {
        $normalized = strtolower(trim($event));

        return in_array($normalized, self::NON_TERMINAL_EVENTS, true);
    }

    public static function isRetryWindowStatus(string $status): bool
    {
        $normalized = strtolower(trim($status));

        return in_array($normalized, self::RETRY_WINDOW_STATUSES, true);
    }

    public static function isTransitionalStatus(string $status): bool
    {
        $normalized = strtolower(trim($status));

        return in_array($normalized, self::TRANSITIONAL_STATUSES, true);
    }
}
