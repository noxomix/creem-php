<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Integration;

final class TransactionsSearchLiveTest extends IntegrationTestCase
{
    public function test_transactions_search_returns_readable_payload(): void
    {
        $client = $this->createIntegrationClient();
        $response = $client->transactions()->search();

        $payload = $response->payload();
        $this->assertIsArray($payload);

        $pagination = $response->pagination();

        if ($pagination !== null) {
            $this->assertGreaterThanOrEqual(1, $pagination->pageNumber());
            $this->assertGreaterThan(0, $pagination->pageSize());
        }

        $this->assertTrue(
            isset($payload['data']) || isset($payload['items']) || isset($payload['transactions']) || $pagination !== null,
            'Expected transaction search payload to expose collection data or pagination metadata.',
        );
    }
}
