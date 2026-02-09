<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Unit;

use Noxomix\CreemPhp\Resource\DiscountResource;
use Noxomix\CreemPhp\Resource\CheckoutResource;
use Noxomix\CreemPhp\Resource\CustomerResource;
use Noxomix\CreemPhp\Resource\LicenseResource;
use Noxomix\CreemPhp\Resource\ProductResource;
use Noxomix\CreemPhp\Resource\SubscriptionResource;
use Noxomix\CreemPhp\Resource\TransactionResource;
use Noxomix\CreemPhp\Subscription\SubscriptionEventType;
use Noxomix\CreemPhp\Subscription\SubscriptionStatus;
use PHPUnit\Framework\TestCase;

final class ResourceAndEnumTest extends TestCase
{
    public function test_subscription_resource_maps_status_to_enum(): void
    {
        $resource = new SubscriptionResource([
            'id' => 'sub_1',
            'mode' => 'test',
            'status' => 'active',
            'customer' => ['id' => 'cust_1'],
            'product' => ['id' => 'prod_1'],
            'items' => [
                ['id' => 'sitem_1', 'product_id' => 'prod_1'],
            ],
            'collection_method' => 'charge_automatically',
            'last_transaction_id' => 'tran_1',
            'next_transaction_date' => '2026-03-01T00:00:00Z',
            'current_period_end_date' => '2026-03-01T00:00:00Z',
        ]);

        $this->assertSame('sub_1', $resource->id());
        $this->assertSame('test', $resource->mode());
        $this->assertSame(SubscriptionStatus::ACTIVE, $resource->status());
        $this->assertSame('active', $resource->statusValue());
        $this->assertSame('cust_1', $resource->customerId());
        $this->assertSame('prod_1', $resource->productId());
        $this->assertCount(1, $resource->items());
        $this->assertSame('charge_automatically', $resource->collectionMethod());
        $this->assertSame('tran_1', $resource->lastTransactionId());
        $this->assertSame('2026-03-01T00:00:00Z', $resource->nextTransactionDate());
        $this->assertSame('2026-03-01T00:00:00Z', $resource->currentPeriodEndDate());
        $this->assertNull($resource->canceledAt());
    }

    public function test_subscription_status_and_event_enum_from_api_value(): void
    {
        $this->assertSame(
            SubscriptionStatus::PAST_DUE,
            SubscriptionStatus::fromApiValue('PAST_DUE'),
        );
        $this->assertSame(
            SubscriptionStatus::UNPAID,
            SubscriptionStatus::fromApiValue('unpaid'),
        );
        $this->assertSame(
            SubscriptionEventType::SUBSCRIPTION_PAID,
            SubscriptionEventType::fromApiValue('subscription.paid'),
        );
        $this->assertNull(SubscriptionEventType::fromApiValue('unknown.event'));
    }

    public function test_product_discount_and_license_resources_expose_core_fields(): void
    {
        $product = new ProductResource([
            'id' => 'prod_1',
            'name' => 'Pro Plan',
            'status' => 'active',
            'price' => 2900,
            'currency' => 'USD',
            'billing_type' => 'recurring',
            'billing_period' => 'every-month',
        ]);

        $discount = new DiscountResource([
            'id' => 'dis_1',
            'code' => 'LAUNCH50',
            'status' => 'active',
            'type' => 'percentage',
            'duration' => 'once',
            'redeem_count' => 7,
        ]);

        $license = new LicenseResource([
            'id' => 'lic_1',
            'status' => 'active',
            'key' => 'ABC123',
            'activation' => 1,
            'activation_limit' => 3,
            'expires_at' => '2025-01-01T00:00:00Z',
            'instance' => [
                'id' => 'inst_1',
                'status' => 'active',
            ],
        ]);

        $this->assertSame('prod_1', $product->id());
        $this->assertSame('Pro Plan', $product->name());
        $this->assertSame(2900, $product->price());
        $this->assertSame('USD', $product->currency());
        $this->assertSame('recurring', $product->billingType());
        $this->assertSame('every-month', $product->billingPeriod());

        $this->assertSame('dis_1', $discount->id());
        $this->assertSame('LAUNCH50', $discount->code());
        $this->assertSame('active', $discount->status());
        $this->assertSame(7, $discount->redeemCount());

        $this->assertSame('lic_1', $license->id());
        $this->assertSame('active', $license->status());
        $this->assertSame('ABC123', $license->key());
        $this->assertSame(1, $license->activation());
        $this->assertSame(3, $license->activationLimit());
        $this->assertSame('2025-01-01T00:00:00Z', $license->expiresAt());
        $this->assertSame('inst_1', $license->instanceId());
        $this->assertSame('active', $license->instanceStatus());
    }

    public function test_checkout_customer_and_transaction_resources_expose_critical_v1_fields(): void
    {
        $checkout = new CheckoutResource([
            'id' => 'ch_1',
            'mode' => 'test',
            'status' => 'pending',
            'checkout_url' => 'https://checkout.creem.io/ch_1',
            'product' => 'prod_1',
            'customer' => ['id' => 'cust_1'],
            'subscription' => ['id' => 'sub_1'],
            'order' => 'ord_1',
            'units' => 2,
            'request_id' => 'order_1',
            'success_url' => 'https://example.com/success',
            'metadata' => ['team' => 'alpha'],
        ]);
        $customer = new CustomerResource([
            'id' => 'cust_1',
            'mode' => 'test',
            'email' => 'user@example.com',
            'name' => 'User Example',
            'country' => 'US',
            'created_at' => '2026-01-01T00:00:00Z',
            'updated_at' => '2026-01-02T00:00:00Z',
        ]);
        $transaction = new TransactionResource([
            'id' => 'tran_1',
            'mode' => 'test',
            'status' => 'paid',
            'amount' => 2900,
            'amount_paid' => 3509,
            'discount_amount' => 0,
            'tax_amount' => 609,
            'refunded_amount' => null,
            'currency' => 'USD',
            'type' => 'invoice',
            'order' => 'ord_1',
            'subscription' => 'sub_1',
            'customer' => ['id' => 'cust_1'],
            'description' => 'Subscription payment',
        ]);

        $this->assertSame('ch_1', $checkout->id());
        $this->assertSame('test', $checkout->mode());
        $this->assertSame('pending', $checkout->status());
        $this->assertSame('https://checkout.creem.io/ch_1', $checkout->checkoutUrl());
        $this->assertSame('prod_1', $checkout->productId());
        $this->assertSame('cust_1', $checkout->customerId());
        $this->assertSame('sub_1', $checkout->subscriptionId());
        $this->assertSame('ord_1', $checkout->orderId());
        $this->assertSame(2, $checkout->units());
        $this->assertSame('order_1', $checkout->requestId());
        $this->assertSame('https://example.com/success', $checkout->successUrl());
        $this->assertSame(['team' => 'alpha'], $checkout->metadata());

        $this->assertSame('cust_1', $customer->id());
        $this->assertSame('test', $customer->mode());
        $this->assertSame('user@example.com', $customer->email());
        $this->assertSame('User Example', $customer->name());
        $this->assertSame('US', $customer->country());
        $this->assertSame('2026-01-01T00:00:00Z', $customer->createdAt());
        $this->assertSame('2026-01-02T00:00:00Z', $customer->updatedAt());

        $this->assertSame('tran_1', $transaction->id());
        $this->assertSame('test', $transaction->mode());
        $this->assertSame('paid', $transaction->status());
        $this->assertSame(2900, $transaction->amount());
        $this->assertSame(3509, $transaction->amountPaid());
        $this->assertSame(0, $transaction->discountAmount());
        $this->assertSame(609, $transaction->taxAmount());
        $this->assertNull($transaction->refundedAmount());
        $this->assertSame('USD', $transaction->currency());
        $this->assertSame('invoice', $transaction->type());
        $this->assertSame('ord_1', $transaction->orderId());
        $this->assertSame('sub_1', $transaction->subscriptionId());
        $this->assertSame('cust_1', $transaction->customerId());
        $this->assertSame('Subscription payment', $transaction->description());
    }
}
