<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Integration;

final class CheckoutsRetrieveLiveTest extends IntegrationTestCase
{
    public function test_checkouts_retrieve_returns_checkout_by_id(): void
    {
        $client = $this->createIntegrationClient();
        $productId = $this->integrationProductId($client);

        $created = $client->checkouts()->create(
            productId: $productId,
            successUrl: 'https://example.com/success',
            units: 1,
            customer: [
                'email' => sprintf('integration+%s@example.com', bin2hex(random_bytes(4))),
            ],
        );

        $checkoutId = $created->id();
        $this->assertNotNull($checkoutId);

        $retrieved = $client->checkouts()->retrieve($checkoutId);

        $this->assertSame($checkoutId, $retrieved->id());
        $this->assertSame($productId, $retrieved->productId());
    }
}
