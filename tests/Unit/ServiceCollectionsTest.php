<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Unit;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Discount\DiscountDuration;
use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Http\HttpResponse;
use Noxomix\CreemPhp\Pagination\PaginatedResponse;
use Noxomix\CreemPhp\Product\BillingPeriod;
use Noxomix\CreemPhp\Product\BillingType;
use Noxomix\CreemPhp\Resource\BillingLinkResource;
use Noxomix\CreemPhp\Resource\CheckoutResource;
use Noxomix\CreemPhp\Resource\CustomerResource;
use Noxomix\CreemPhp\Resource\DiscountResource;
use Noxomix\CreemPhp\Resource\LicenseResource;
use Noxomix\CreemPhp\Resource\ProductResource;
use Noxomix\CreemPhp\Resource\SubscriptionResource;
use Noxomix\CreemPhp\Resource\TransactionResource;
use Noxomix\CreemPhp\Subscription\SubscriptionStatus;
use Noxomix\CreemPhp\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

final class ServiceCollectionsTest extends TestCase
{
    public function test_it_reuses_domain_service_instances(): void
    {
        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
            ],
            transport: new FakeTransport([
                new HttpResponse(200, [], '{}'),
            ]),
        );

        $this->assertSame($client->checkouts(), $client->checkouts());
        $this->assertSame($client->subscriptions(), $client->subscriptions());
        $this->assertSame($client->customers(), $client->customers());
        $this->assertSame($client->transactions(), $client->transactions());
        $this->assertSame($client->products(), $client->products());
        $this->assertSame($client->discounts(), $client->discounts());
        $this->assertSame($client->licenses(), $client->licenses());
    }

    public function test_checkouts_service_maps_create_and_retrieve_endpoints(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"id":"chk_1","status":"pending","request_id":"checkout_req_1"}'),
            new HttpResponse(200, [], '{"id":"chk_1","status":"paid"}'),
        ]);

        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
            ],
            transport: $transport,
        );

        $checkout = $client->checkouts()->create(
            productId: 'prod_1',
            successUrl: 'https://example.com/success',
            requestId: 'checkout_req_1',
        );
        $retrieved = $client->checkouts()->retrieve('chk_1');

        $requests = $transport->requests();
        $this->assertInstanceOf(CheckoutResource::class, $checkout);
        $this->assertInstanceOf(CheckoutResource::class, $retrieved);
        $this->assertSame('chk_1', $checkout->id());
        $this->assertSame('checkout_req_1', $checkout->requestId());
        $this->assertSame('paid', $retrieved->status());

        $this->assertSame('POST', $requests[0]->method());
        $this->assertSame('/v1/checkouts', $requests[0]->path());
        $this->assertSame('prod_1', $requests[0]->body()['product_id']);
        $this->assertSame('checkout_req_1', $requests[0]->body()['request_id']);

        $this->assertSame('GET', $requests[1]->method());
        $this->assertSame('/v1/checkouts', $requests[1]->path());
        $this->assertSame(['checkout_id' => 'chk_1'], $requests[1]->query());
    }

    public function test_subscriptions_service_maps_all_core_actions(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"id":"sub_1","status":"active"}'),
            new HttpResponse(200, [], '{"id":"sub_1","status":"active"}'),
            new HttpResponse(200, [], '{"id":"sub_1","status":"active"}'),
            new HttpResponse(200, [], '{"id":"sub_1","status":"active"}'),
            new HttpResponse(200, [], '{"id":"sub_1","status":"scheduled_cancel"}'),
            new HttpResponse(200, [], '{"id":"sub_1","status":"paused"}'),
            new HttpResponse(200, [], '{"id":"sub_1","status":"active"}'),
        ]);

        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
            ],
            transport: $transport,
        );

        $retrieved = $client->subscriptions()->retrieve('sub_1');
        $reconciled = $client->subscriptions()->reconcile('sub_1');
        $updated = $client->subscriptions()->update(
            subscriptionId: 'sub_1',
            updateBehavior: 'proration-charge-immediately',
            requestId: 'update_req_1',
        );
        $upgraded = $client->subscriptions()->upgrade(
            subscriptionId: 'sub_1',
            productId: 'prod_new',
            requestId: 'upgrade_req_1',
        );
        $canceled = $client->subscriptions()->cancel(
            subscriptionId: 'sub_1',
            mode: 'scheduled',
            onExecute: 'cancel',
            requestId: 'cancel_req_1',
        );
        $paused = $client->subscriptions()->pause('sub_1', 'pause_req_1');
        $resumed = $client->subscriptions()->resume('sub_1', 'resume_req_1');

        $requests = $transport->requests();
        $this->assertInstanceOf(SubscriptionResource::class, $retrieved);
        $this->assertInstanceOf(SubscriptionResource::class, $reconciled);
        $this->assertInstanceOf(SubscriptionResource::class, $updated);
        $this->assertInstanceOf(SubscriptionResource::class, $upgraded);
        $this->assertInstanceOf(SubscriptionResource::class, $canceled);
        $this->assertInstanceOf(SubscriptionResource::class, $paused);
        $this->assertInstanceOf(SubscriptionResource::class, $resumed);
        $this->assertSame(SubscriptionStatus::ACTIVE, $retrieved->status());
        $this->assertSame(SubscriptionStatus::SCHEDULED_CANCEL, $canceled->status());

        $this->assertSame('/v1/subscriptions', $requests[0]->path());
        $this->assertSame('/v1/subscriptions', $requests[1]->path());
        $this->assertSame('/v1/subscriptions/sub_1', $requests[2]->path());
        $this->assertSame('/v1/subscriptions/sub_1/upgrade', $requests[3]->path());
        $this->assertSame('/v1/subscriptions/sub_1/cancel', $requests[4]->path());
        $this->assertSame('/v1/subscriptions/sub_1/pause', $requests[5]->path());
        $this->assertSame('/v1/subscriptions/sub_1/resume', $requests[6]->path());

        $this->assertSame('proration-charge-immediately', $requests[2]->body()['update_behavior']);
        $this->assertSame('update_req_1', $requests[2]->body()['request_id']);
        $this->assertSame('prod_new', $requests[3]->body()['product_id']);
        $this->assertSame('proration-charge-immediately', $requests[3]->body()['update_behavior']);
        $this->assertSame('upgrade_req_1', $requests[3]->body()['request_id']);
        $this->assertSame('cancel_req_1', $requests[4]->body()['request_id']);
        $this->assertSame('scheduled', $requests[4]->body()['mode']);
        $this->assertSame('cancel', $requests[4]->body()['onExecute']);
        $this->assertSame('pause_req_1', $requests[5]->body()['request_id']);
        $this->assertSame('resume_req_1', $requests[6]->body()['request_id']);
    }

    public function test_customers_and_transactions_expose_paginated_responses(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"id":"cus_1"}'),
            new HttpResponse(200, [], '{"url":"https://billing.example.com/session_1"}'),
            new HttpResponse(200, [], '{"id":"txn_1","status":"succeeded"}'),
            new HttpResponse(
                200,
                [],
                '{"data":[],"page_number":2,"page_size":25,"total_items":80,"total_pages":4}',
            ),
            new HttpResponse(
                200,
                [],
                '{"data":[],"meta":{"pagination":{"page_number":"3","page_size":"10","total_items":"31","total_pages":"4"}}}',
            ),
        ]);

        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
            ],
            transport: $transport,
        );

        $customer = $client->customers()->retrieve('cus_1');
        $billingLink = $client->customers()->createBillingLink('cus_1', 'billing_1');
        $transaction = $client->transactions()->retrieve('txn_1');
        $customers = $client->customers()->list(pageNumber: 2, pageSize: 25);
        $transactions = $client->transactions()->search([
            'page_number' => 3,
            'page_size' => 10,
            'status' => 'succeeded',
        ]);

        $this->assertInstanceOf(CustomerResource::class, $customer);
        $this->assertInstanceOf(BillingLinkResource::class, $billingLink);
        $this->assertInstanceOf(TransactionResource::class, $transaction);
        $this->assertSame('cus_1', $customer->id());
        $this->assertSame('https://billing.example.com/session_1', $billingLink->url());
        $this->assertSame('succeeded', $transaction->status());
        $this->assertInstanceOf(PaginatedResponse::class, $customers);
        $this->assertInstanceOf(PaginatedResponse::class, $transactions);
        $this->assertSame(2, $customers->pagination()?->pageNumber());
        $this->assertSame(25, $customers->pagination()?->pageSize());
        $this->assertSame(3, $transactions->pagination()?->pageNumber());
        $this->assertSame(10, $transactions->pagination()?->pageSize());

        $requests = $transport->requests();
        $this->assertSame('/v1/customers', $requests[0]->path());
        $this->assertSame('/v1/customers/billing', $requests[1]->path());
        $this->assertSame('billing_1', $requests[1]->body()['request_id']);
        $this->assertSame('/v1/transactions', $requests[2]->path());
    }

    public function test_customers_service_can_retrieve_by_email_and_map_customer_portal_link(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"id":"cus_2","email":"user@example.com"}'),
            new HttpResponse(200, [], '{"customer_portal_link":"https://creem.io/portal/cust_2?token=abc"}'),
        ]);

        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
            ],
            transport: $transport,
        );

        $customer = $client->customers()->retrieveByEmail('user@example.com');
        $billingLink = $client->customers()->createBillingLink('cus_2', 'billing_2');

        $this->assertInstanceOf(CustomerResource::class, $customer);
        $this->assertInstanceOf(BillingLinkResource::class, $billingLink);
        $this->assertSame('cus_2', $customer->id());
        $this->assertSame('https://creem.io/portal/cust_2?token=abc', $billingLink->url());

        $requests = $transport->requests();
        $this->assertSame('/v1/customers', $requests[0]->path());
        $this->assertSame(['email' => 'user@example.com'], $requests[0]->query());
        $this->assertSame('/v1/customers/billing', $requests[1]->path());
    }

    public function test_products_discounts_and_licenses_map_core_endpoints(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(
                200,
                [],
                '{"id":"prod_1","name":"Pro Plan","status":"active","price":2900,"currency":"USD","billing_type":"recurring","billing_period":"every-month"}',
            ),
            new HttpResponse(
                200,
                [],
                '{"id":"prod_1","name":"Pro Plan","status":"active","price":2900,"currency":"USD","billing_type":"recurring","billing_period":"every-month"}',
            ),
            new HttpResponse(
                200,
                [],
                '{"items":[{"id":"prod_1"},{"id":"prod_2"}],"pagination":{"current_page":1,"total_records":2,"total_pages":1}}',
            ),
            new HttpResponse(
                200,
                [],
                '{"id":"dis_1","code":"LAUNCH50","status":"active","type":"percentage","duration":"once","redeem_count":5}',
            ),
            new HttpResponse(
                200,
                [],
                '{"id":"dis_1","code":"LAUNCH50","status":"active","type":"percentage","duration":"once","redeem_count":5}',
            ),
            new HttpResponse(
                200,
                [],
                '{"id":"dis_1","code":"LAUNCH50","status":"active","type":"percentage","duration":"once","redeem_count":5}',
            ),
            new HttpResponse(200, [], '{"deleted":true}'),
            new HttpResponse(
                200,
                [],
                '{"id":"lic_1","status":"active","key":"ABC123","activation":1,"activation_limit":3,"instance":{"id":"inst_1","status":"active"}}',
            ),
            new HttpResponse(
                200,
                [],
                '{"id":"lic_1","status":"active","key":"ABC123","activation":1,"activation_limit":3,"instance":{"id":"inst_1","status":"active"}}',
            ),
            new HttpResponse(
                200,
                [],
                '{"id":"lic_1","status":"inactive","key":"ABC123","activation":0,"activation_limit":3,"instance":{"id":"inst_1","status":"inactive"}}',
            ),
        ]);

        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
            ],
            transport: $transport,
        );

        $product = $client->products()->create(
            name: 'Pro Plan',
            price: 2900,
            currency: 'usd',
            billingType: BillingType::RECURRING,
            billingPeriod: BillingPeriod::EVERY_MONTH,
            requestId: 'product_req_1',
        );
        $retrievedProduct = $client->products()->retrieve('prod_1');
        $productList = $client->products()->search(pageNumber: 1, pageSize: 2);

        $discount = $client->discounts()->create(
            name: 'Launch Promo',
            type: 'percentage',
            duration: DiscountDuration::ONCE,
            appliesToProducts: ['prod_1'],
            percentage: 50,
        );
        $discountById = $client->discounts()->retrieve(discountId: 'dis_1');
        $discountByCode = $client->discounts()->retrieve(discountCode: 'LAUNCH50');
        $client->discounts()->delete('dis_1');

        $activatedLicense = $client->licenses()->activate(
            key: 'ABC123',
            instanceName: 'server-1',
        );
        $validatedLicense = $client->licenses()->validate(
            key: 'ABC123',
            instanceId: 'inst_1',
        );
        $deactivatedLicense = $client->licenses()->deactivate(
            key: 'ABC123',
            instanceId: 'inst_1',
        );

        $this->assertInstanceOf(ProductResource::class, $product);
        $this->assertInstanceOf(ProductResource::class, $retrievedProduct);
        $this->assertInstanceOf(PaginatedResponse::class, $productList);
        $this->assertInstanceOf(DiscountResource::class, $discount);
        $this->assertInstanceOf(DiscountResource::class, $discountById);
        $this->assertInstanceOf(DiscountResource::class, $discountByCode);
        $this->assertInstanceOf(LicenseResource::class, $activatedLicense);
        $this->assertInstanceOf(LicenseResource::class, $validatedLicense);
        $this->assertInstanceOf(LicenseResource::class, $deactivatedLicense);
        $this->assertSame('prod_1', $product->id());
        $this->assertSame('USD', $product->currency());
        $this->assertSame(1, $productList->pagination()?->pageNumber());
        $this->assertSame(2, $productList->pagination()?->pageSize());
        $this->assertSame('dis_1', $discountById->id());
        $this->assertSame('LAUNCH50', $discountByCode->code());
        $this->assertSame('inst_1', $activatedLicense->instanceId());
        $this->assertSame('inactive', $deactivatedLicense->instanceStatus());

        $requests = $transport->requests();
        $this->assertSame('/v1/products', $requests[0]->path());
        $this->assertSame('POST', $requests[0]->method());
        $this->assertSame('product_req_1', $requests[0]->body()['request_id']);
        $this->assertSame('every-month', $requests[0]->body()['billing_period']);
        $this->assertSame('/v1/products', $requests[1]->path());
        $this->assertSame(['product_id' => 'prod_1'], $requests[1]->query());
        $this->assertSame('/v1/products/search', $requests[2]->path());
        $this->assertSame(['page_number' => 1, 'page_size' => 2], $requests[2]->query());
        $this->assertSame('/v1/discounts', $requests[3]->path());
        $this->assertSame('/v1/discounts', $requests[4]->path());
        $this->assertSame(['discount_id' => 'dis_1'], $requests[4]->query());
        $this->assertSame('/v1/discounts', $requests[5]->path());
        $this->assertSame(['discount_code' => 'LAUNCH50'], $requests[5]->query());
        $this->assertSame('/v1/discounts/dis_1/delete', $requests[6]->path());
        $this->assertSame('DELETE', $requests[6]->method());
        $this->assertSame('/v1/licenses/activate', $requests[7]->path());
        $this->assertSame('/v1/licenses/validate', $requests[8]->path());
        $this->assertSame('/v1/licenses/deactivate', $requests[9]->path());
    }

    public function test_customers_list_throws_for_invalid_pagination_values(): void
    {
        $client = new CreemClient(
            config: [
                'api_key' => 'creem_test_key',
                'mode' => 'test',
            ],
            transport: new FakeTransport([
                new HttpResponse(200, [], '{}'),
            ]),
        );

        $this->expectException(InvalidConfigurationException::class);

        $client->customers()->list(pageNumber: 0, pageSize: 25);
    }
}
