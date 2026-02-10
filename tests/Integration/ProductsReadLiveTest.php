<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Integration;

final class ProductsReadLiveTest extends IntegrationTestCase
{
    public function test_products_retrieve_returns_product_by_id(): void
    {
        $client = $this->createIntegrationClient();
        $productId = $this->integrationProductId($client);

        $product = $client->products()->retrieve($productId);

        $this->assertSame($productId, $product->id());
        $this->assertNotNull($product->currency());
    }

    public function test_products_search_returns_collection_payload(): void
    {
        $client = $this->createIntegrationClient();
        $response = $client->products()->search(pageNumber: 1, pageSize: 10);

        $payload = $response->payload();
        $this->assertIsArray($payload);

        $pagination = $response->pagination();

        if ($pagination !== null) {
            $this->assertGreaterThanOrEqual(1, $pagination->pageNumber());
            $this->assertGreaterThan(0, $pagination->pageSize());
        }

        $this->assertTrue(
            isset($payload['data']) || isset($payload['items']) || isset($payload['products']) || $pagination !== null,
            'Expected product search payload to expose collection data or pagination metadata.',
        );
    }
}
