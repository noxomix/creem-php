<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Subscription;

final class SubscriptionLifecycle
{
    /** @var array<int, string> */
    private const TERMINAL_EVENTS = [
        'subscription.canceled',
    ];

    /** @var array<int, string> */
    private const NON_TERMINAL_EVENTS = [
        'checkout.completed',
        'subscription.paid',
        'subscription.active',
        'subscription.update',
        'subscription.trialing',
        'subscription.paused',
        'subscription.scheduled_cancel',
        'subscription.past_due',
        'subscription.expired',
    ];

    /** @var array<int, string> */
    private const RETRY_WINDOW_STATUSES = [
        'unpaid',
        'past_due',
        'expired',
    ];

    /** @var array<int, string> */
    private const TRANSITIONAL_STATUSES = [
        'scheduled_cancel',
        'trialing',
        'paused',
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
