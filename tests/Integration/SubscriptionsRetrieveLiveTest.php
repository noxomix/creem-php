<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Integration;

final class SubscriptionsRetrieveLiveTest extends IntegrationTestCase
{
    public function test_subscriptions_retrieve_returns_subscription(): void
    {
        $client = $this->createIntegrationClient();
        $subscriptionId = $this->optionalEnv('CREEM_INTEGRATION_SUBSCRIPTION_ID');

        if ($subscriptionId === null) {
            $this->markTestSkipped('Missing CREEM_INTEGRATION_SUBSCRIPTION_ID for subscriptions retrieve integration test.');
        }

        $subscription = $client->subscriptions()->retrieve($subscriptionId);

        $this->assertSame($subscriptionId, $subscription->id());
        $this->assertNotNull($subscription->statusValue());
    }
}
