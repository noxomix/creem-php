<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Unit;

use Noxomix\CreemPhp\Subscription\SubscriptionLifecycle;
use PHPUnit\Framework\TestCase;

final class SubscriptionLifecycleTest extends TestCase
{
    public function test_it_identifies_terminal_events(): void
    {
        $this->assertTrue(SubscriptionLifecycle::isTerminalEvent('subscription.canceled'));
        $this->assertFalse(SubscriptionLifecycle::isTerminalEvent('subscription.active'));
    }

    public function test_it_identifies_non_terminal_events(): void
    {
        $this->assertTrue(SubscriptionLifecycle::isNonTerminalEvent('checkout.completed'));
        $this->assertTrue(SubscriptionLifecycle::isNonTerminalEvent('subscription.paid'));
        $this->assertTrue(SubscriptionLifecycle::isNonTerminalEvent('subscription.active'));
        $this->assertTrue(SubscriptionLifecycle::isNonTerminalEvent('subscription.past_due'));
        $this->assertFalse(SubscriptionLifecycle::isNonTerminalEvent('subscription.canceled'));
    }

    public function test_it_identifies_retry_window_and_transitional_statuses(): void
    {
        $this->assertTrue(SubscriptionLifecycle::isRetryWindowStatus('unpaid'));
        $this->assertTrue(SubscriptionLifecycle::isRetryWindowStatus('past_due'));
        $this->assertTrue(SubscriptionLifecycle::isRetryWindowStatus('expired'));
        $this->assertFalse(SubscriptionLifecycle::isRetryWindowStatus('active'));

        $this->assertTrue(SubscriptionLifecycle::isTransitionalStatus('paused'));
        $this->assertTrue(SubscriptionLifecycle::isTransitionalStatus('trialing'));
        $this->assertFalse(SubscriptionLifecycle::isTransitionalStatus('canceled'));
    }
}
