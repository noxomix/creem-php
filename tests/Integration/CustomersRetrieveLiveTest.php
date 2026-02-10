<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Integration;

final class CustomersRetrieveLiveTest extends IntegrationTestCase
{
    public function test_customers_retrieve_by_email_returns_customer(): void
    {
        $client = $this->createIntegrationClient();
        $email = $this->optionalEnv('CREEM_INTEGRATION_CUSTOMER_EMAIL');

        if ($email === null) {
            $this->markTestSkipped('Missing CREEM_INTEGRATION_CUSTOMER_EMAIL for customers retrieve integration test.');
        }

        $customer = $client->customers()->retrieve(email: $email);

        $this->assertNotNull($customer->id());
        $this->assertSame(strtolower($email), strtolower((string) $customer->email()));
    }
}
