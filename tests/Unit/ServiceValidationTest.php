<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Unit;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Discount\DiscountDuration;
use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Http\HttpResponse;
use Noxomix\CreemPhp\Product\BillingPeriod;
use Noxomix\CreemPhp\Product\BillingType;
use Noxomix\CreemPhp\Product\TaxCategory;
use Noxomix\CreemPhp\Product\TaxMode;
use Noxomix\CreemPhp\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

final class ServiceValidationTest extends TestCase
{
    public function test_checkouts_create_throws_for_empty_product_id(): void
    {
        $client = $this->client();

        $this->expectException(InvalidConfigurationException::class);

        $client->checkouts()->create(' ');
    }

    public function test_checkouts_create_throws_for_empty_success_url_when_provided(): void
    {
        $client = $this->client();

        $this->expectException(InvalidConfigurationException::class);

        $client->checkouts()->create('prod_1', ' ');
    }

    public function test_checkouts_create_throws_for_invalid_units(): void
    {
        $client = $this->client();

        $this->expectException(InvalidConfigurationException::class);

        $client->checkouts()->create(
            productId: 'prod_1',
            units: 0,
        );
    }

    public function test_checkouts_create_throws_when_customer_contains_both_id_and_email(): void
    {
        $client = $this->client();

        $this->expectException(InvalidConfigurationException::class);

        $client->checkouts()->create(
            productId: 'prod_1',
            customer: [
                'id' => 'cus_1',
                'email' => 'user@example.com',
            ],
        );
    }

    public function test_checkouts_create_throws_for_too_many_custom_fields(): void
    {
        $client = $this->client();

        $this->expectException(InvalidConfigurationException::class);

        $client->checkouts()->create(
            productId: 'prod_1',
            customFields: [
                ['type' => 'text', 'key' => 'f1', 'label' => 'Field 1'],
                ['type' => 'text', 'key' => 'f2', 'label' => 'Field 2'],
                ['type' => 'text', 'key' => 'f3', 'label' => 'Field 3'],
                ['type' => 'text', 'key' => 'f4', 'label' => 'Field 4'],
            ],
        );
    }

    public function test_checkouts_create_accepts_customer_prefill_discount_and_metadata(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"id":"chk_1"}'),
        ]);
        $client = $this->client($transport);

        $client->checkouts()->create(
            productId: 'prod_1',
            units: 3,
            discountCode: 'LAUNCH50',
            customer: ['email' => 'user@example.com'],
            metadata: ['source' => 'unit-test'],
            customFields: [
                [
                    'type' => 'checkbox',
                    'key' => 'termsAccepted',
                    'label' => 'Accept Terms',
                    'checkbox' => ['label' => 'I agree'],
                ],
            ],
        );

        $request = $transport->requests()[0];
        $this->assertSame(3, $request->body()['units']);
        $this->assertSame('LAUNCH50', $request->body()['discount_code']);
        $this->assertSame(['email' => 'user@example.com'], $request->body()['customer']);
        $this->assertSame(['source' => 'unit-test'], $request->body()['metadata']);
        $this->assertSame('checkbox', $request->body()['custom_fields'][0]['type']);
    }

    public function test_subscriptions_update_throws_for_invalid_update_behavior(): void
    {
        $client = $this->client();

        $this->expectException(InvalidConfigurationException::class);

        $client->subscriptions()->update('sub_1', 'prorate');
    }

    public function test_subscriptions_update_supports_items_payload(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"id":"sub_1","status":"active"}'),
        ]);
        $client = $this->client($transport);

        $client->subscriptions()->update(
            subscriptionId: 'sub_1',
            items: [
                [
                    'product_id' => 'prod_2',
                    'quantity' => 5,
                ],
            ],
        );

        $request = $transport->requests()[0];
        $this->assertSame('proration-charge', $request->body()['update_behavior']);
        $this->assertSame('prod_2', $request->body()['items'][0]['product_id']);
        $this->assertSame(5, $request->body()['items'][0]['quantity']);
    }

    public function test_subscriptions_upgrade_accepts_custom_update_behavior(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"id":"sub_1","status":"active"}'),
        ]);
        $client = $this->client($transport);

        $client->subscriptions()->upgrade(
            subscriptionId: 'sub_1',
            productId: 'prod_2',
            updateBehavior: 'proration-none',
        );

        $request = $transport->requests()[0];
        $this->assertSame('prod_2', $request->body()['product_id']);
        $this->assertSame('proration-none', $request->body()['update_behavior']);
    }

    public function test_subscriptions_cancel_throws_when_on_execute_is_used_without_scheduled_mode(): void
    {
        $client = $this->client();

        $this->expectException(InvalidConfigurationException::class);

        $client->subscriptions()->cancel('sub_1', mode: 'immediate', onExecute: 'cancel');
    }

    public function test_products_create_accepts_enum_billing_type(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"id":"prod_1","billing_type":"onetime"}'),
        ]);
        $client = $this->client($transport);

        $client->products()->create(
            name: 'Starter',
            price: 1900,
            currency: 'usd',
            billingType: BillingType::ONETIME,
        );

        $request = $transport->requests()[0];
        $this->assertSame('onetime', $request->body()['billing_type']);
        $this->assertSame('USD', $request->body()['currency']);
    }

    public function test_products_create_accepts_billing_period_enum(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"id":"prod_1","billing_period":"every-month"}'),
        ]);
        $client = $this->client($transport);

        $client->products()->create(
            name: 'Pro Plan',
            price: 2900,
            currency: 'usd',
            billingType: BillingType::RECURRING,
            billingPeriod: BillingPeriod::EVERY_MONTH,
        );

        $request = $transport->requests()[0];
        $this->assertSame('every-month', $request->body()['billing_period']);
    }

    public function test_products_create_accepts_tax_enums_and_optional_fields(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"id":"prod_1","tax_mode":"exclusive","tax_category":"saas"}'),
        ]);
        $client = $this->client($transport);

        $client->products()->create(
            name: 'Pro Plan',
            price: 2900,
            currency: 'USD',
            billingType: BillingType::RECURRING,
            billingPeriod: BillingPeriod::EVERY_MONTH,
            description: 'Full access',
            imageUrl: 'https://example.com/product.png',
            taxMode: TaxMode::EXCLUSIVE,
            taxCategory: TaxCategory::SAAS,
            defaultSuccessUrl: 'https://example.com/success',
            customFields: [
                [
                    'type' => 'text',
                    'key' => 'company',
                    'label' => 'Company',
                    'text' => ['max_length' => 200],
                ],
            ],
            abandonedCartRecoveryEnabled: true,
        );

        $request = $transport->requests()[0];
        $this->assertSame('exclusive', $request->body()['tax_mode']);
        $this->assertSame('saas', $request->body()['tax_category']);
        $this->assertSame('https://example.com/product.png', $request->body()['image_url']);
        $this->assertSame('https://example.com/success', $request->body()['default_success_url']);
        $this->assertTrue($request->body()['abandoned_cart_recovery_enabled']);
    }

    public function test_products_create_throws_for_invalid_optional_urls(): void
    {
        $client = $this->client();

        $this->expectException(InvalidConfigurationException::class);

        $client->products()->create(
            name: 'Pro Plan',
            price: 2900,
            currency: 'USD',
            billingType: BillingType::RECURRING,
            billingPeriod: BillingPeriod::EVERY_MONTH,
            imageUrl: 'not-a-url',
        );
    }

    public function test_products_create_throws_for_too_many_custom_fields(): void
    {
        $client = $this->client();

        $this->expectException(InvalidConfigurationException::class);

        $client->products()->create(
            name: 'Pro Plan',
            price: 2900,
            currency: 'USD',
            billingType: BillingType::RECURRING,
            billingPeriod: BillingPeriod::EVERY_MONTH,
            customFields: [
                ['type' => 'text', 'key' => 'f1', 'label' => 'Field 1'],
                ['type' => 'text', 'key' => 'f2', 'label' => 'Field 2'],
                ['type' => 'text', 'key' => 'f3', 'label' => 'Field 3'],
                ['type' => 'text', 'key' => 'f4', 'label' => 'Field 4'],
            ],
        );
    }

    public function test_products_create_accepts_once_billing_period_for_onetime_products(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"id":"prod_1","billing_period":"once"}'),
        ]);
        $client = $this->client($transport);

        $client->products()->create(
            name: 'Starter Plan',
            price: 1900,
            currency: 'USD',
            billingType: BillingType::ONETIME,
            billingPeriod: BillingPeriod::ONCE,
        );

        $request = $transport->requests()[0];
        $this->assertSame('once', $request->body()['billing_period']);
    }

    public function test_products_create_supports_custom_billing_period_string(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"id":"prod_1","billing_period":"every-quarter"}'),
        ]);
        $client = $this->client($transport);

        $client->products()->create(
            name: 'Pro Plan',
            price: 2900,
            currency: 'USD',
            billingType: BillingType::RECURRING,
            billingPeriod: 'every-quarter',
        );

        $request = $transport->requests()[0];
        $this->assertSame('every-quarter', $request->body()['billing_period']);
    }

    public function test_products_create_throws_for_invalid_recurring_configuration(): void
    {
        $client = $this->client();

        $this->expectException(InvalidConfigurationException::class);

        $client->products()->create(
            name: 'Pro Plan',
            price: 2900,
            currency: 'USD',
            billingType: BillingType::RECURRING,
            billingPeriod: null,
        );
    }

    public function test_discounts_create_accepts_duration_enum(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"id":"dis_1"}'),
        ]);
        $client = $this->client($transport);

        $client->discounts()->create(
            name: 'Repeat Promo',
            type: 'percentage',
            duration: DiscountDuration::REPEATING,
            appliesToProducts: ['prod_1'],
            percentage: 10,
            durationInMonths: 2,
        );

        $request = $transport->requests()[0];
        $this->assertSame('repeating', $request->body()['duration']);
        $this->assertSame(2, $request->body()['duration_in_months']);
    }

    public function test_discounts_create_throws_for_invalid_fixed_configuration(): void
    {
        $client = $this->client();

        $this->expectException(InvalidConfigurationException::class);

        $client->discounts()->create(
            name: 'Broken Fixed',
            type: 'fixed',
            duration: DiscountDuration::ONCE,
            appliesToProducts: ['prod_1'],
            amount: 1000,
            currency: null,
        );
    }

    public function test_licenses_validate_throws_for_empty_instance_id(): void
    {
        $client = $this->client();

        $this->expectException(InvalidConfigurationException::class);

        $client->licenses()->validate('KEY_1', ' ');
    }

    public function test_create_billing_link_throws_for_empty_customer_id(): void
    {
        $client = $this->client();

        $this->expectException(InvalidConfigurationException::class);

        $client->customers()->createBillingLink(' ');
    }

    public function test_customers_retrieve_throws_when_neither_customer_id_nor_email_is_provided(): void
    {
        $client = $this->client();

        $this->expectException(InvalidConfigurationException::class);

        $client->customers()->retrieve();
    }

    public function test_customers_retrieve_throws_when_customer_id_and_email_are_both_provided(): void
    {
        $client = $this->client();

        $this->expectException(InvalidConfigurationException::class);

        $client->customers()->retrieve(customerId: 'cus_1', email: 'user@example.com');
    }

    public function test_customers_retrieve_throws_for_invalid_email(): void
    {
        $client = $this->client();

        $this->expectException(InvalidConfigurationException::class);

        $client->customers()->retrieve(email: 'not-an-email');
    }

    private function client(?FakeTransport $transport = null): CreemClient
    {
        return new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
            ],
            transport: $transport ?? new FakeTransport([
                new HttpResponse(200, [], '{}'),
            ]),
        );
    }
}
