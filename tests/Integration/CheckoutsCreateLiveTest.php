<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Integration;

final class CheckoutsCreateLiveTest extends IntegrationTestCase
{
    public function test_checkouts_create_supports_prefilled_customer_data(): void
    {
        $client = $this->createIntegrationClient();
        $productId = $this->integrationProductId($client);
        $discountCode = $this->optionalEnv('CREEM_INTEGRATION_DISCOUNT_CODE');

        $checkout = $client->checkouts()->create(
            productId: $productId,
            successUrl: 'https://example.com/success',
            units: 1,
            discountCode: $discountCode,
            customer: [
                'email' => sprintf('integration+%s@example.com', bin2hex(random_bytes(4))),
            ],
            metadata: [
                'suite' => 'integration',
                'component' => 'checkouts-create',
            ],
        );

        $this->assertNotNull($checkout->id());
        $this->assertNotNull($checkout->checkoutUrl());
        $this->assertSame($productId, $checkout->productId());
        $this->assertSame(1, $checkout->units());
    }
}
