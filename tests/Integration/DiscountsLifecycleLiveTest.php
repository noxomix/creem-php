<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Integration;

use Noxomix\CreemPhp\Discount\DiscountDuration;

final class DiscountsLifecycleLiveTest extends IntegrationTestCase
{
    public function test_discounts_create_retrieve_and_delete_cycle(): void
    {
        $client = $this->createIntegrationClient();
        $productId = $this->integrationProductId($client);
        $code = sprintf('INT%s', strtoupper(bin2hex(random_bytes(3))));

        $created = $client->discounts()->create(
            name: sprintf('Integration %s', $code),
            type: 'percentage',
            duration: DiscountDuration::ONCE,
            appliesToProducts: [$productId],
            percentage: 10,
            code: $code,
        );

        $discountId = $created->id();
        $this->assertNotNull($discountId);
        $this->registerDiscountForCleanup($discountId);
        $this->assertSame('percentage', $created->type());

        $retrievedById = $client->discounts()->retrieve(discountId: $discountId);
        $this->assertSame($discountId, $retrievedById->id());

        $retrievedByCode = $client->discounts()->retrieve(discountCode: $code);
        $this->assertSame($code, $retrievedByCode->code());

        $client->discounts()->delete($discountId);
        $this->addToAssertionCount(1);
    }
}
