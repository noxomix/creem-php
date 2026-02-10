<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Integration;

final class CustomersListLiveTest extends IntegrationTestCase
{
    public function test_customers_list_live_response_is_readable(): void
    {
        $client = $this->createIntegrationClient();
        $response = $client->customers()->list(pageNumber: 1, pageSize: 10);

        $payload = $response->payload();
        $this->assertIsArray($payload);

        $pagination = $response->pagination();

        if ($pagination !== null) {
            $this->assertGreaterThanOrEqual(1, $pagination->pageNumber());
            $this->assertGreaterThan(0, $pagination->pageSize());
        }

        $this->assertTrue(
            isset($payload['data']) || isset($payload['items']) || isset($payload['customers']) || $pagination !== null,
            'Expected customer list payload to expose collection data or pagination metadata.',
        );
    }
}
