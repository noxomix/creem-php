<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Integration;

use Noxomix\CreemPhp\Product\BillingPeriod;
use Noxomix\CreemPhp\Product\BillingType;
use Noxomix\CreemPhp\Product\TaxCategory;
use Noxomix\CreemPhp\Product\TaxMode;

final class ProductsCreateLiveTest extends IntegrationTestCase
{
    public function test_products_create_supports_optional_fields(): void
    {
        $client = $this->createIntegrationClient();

        $product = $client->products()->create(
            name: sprintf('SDK Integration %s', bin2hex(random_bytes(4))),
            description: 'Integration coverage product',
            imageUrl: 'https://example.com/product.png',
            price: 1900,
            currency: 'USD',
            billingType: BillingType::RECURRING,
            billingPeriod: BillingPeriod::EVERY_MONTH,
            taxMode: TaxMode::EXCLUSIVE,
            taxCategory: TaxCategory::SAAS,
            defaultSuccessUrl: 'https://example.com/success',
            customFields: [
                [
                    'type' => 'text',
                    'key' => 'company',
                    'label' => 'Company Name',
                    'text' => [
                        'min_length' => 1,
                        'max_length' => 100,
                    ],
                ],
            ],
            abandonedCartRecoveryEnabled: false,
            requestId: sprintf('integration-product-%s', bin2hex(random_bytes(4))),
        );

        $this->assertNotNull($product->id());
        $this->assertSame('USD', $product->currency());
        $this->assertSame('recurring', $product->billingType());
        $this->assertSame('every-month', $product->billingPeriod());
    }
}
