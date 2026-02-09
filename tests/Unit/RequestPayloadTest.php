<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Unit;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Request\Checkouts\CreateCheckoutRequest;
use Noxomix\CreemPhp\Request\Customers\CreateBillingLinkRequest;
use Noxomix\CreemPhp\Request\Discounts\CreateDiscountRequest;
use Noxomix\CreemPhp\Request\Licenses\ActivateLicenseRequest;
use Noxomix\CreemPhp\Request\Licenses\DeactivateLicenseRequest;
use Noxomix\CreemPhp\Request\Licenses\ValidateLicenseRequest;
use Noxomix\CreemPhp\Request\Products\CreateProductRequest;
use Noxomix\CreemPhp\Request\Subscriptions\CancelSubscriptionRequest;
use Noxomix\CreemPhp\Request\Subscriptions\PauseSubscriptionRequest;
use Noxomix\CreemPhp\Request\Subscriptions\ResumeSubscriptionRequest;
use Noxomix\CreemPhp\Request\Subscriptions\UpdateSubscriptionRequest;
use Noxomix\CreemPhp\Request\Subscriptions\UpgradeSubscriptionRequest;
use PHPUnit\Framework\TestCase;

final class RequestPayloadTest extends TestCase
{
    public function test_create_checkout_request_builds_payload_and_request_id(): void
    {
        $request = new CreateCheckoutRequest(
            productId: 'prod_1',
            successUrl: 'https://example.com/success',
            requestId: 'checkout_1',
            extra: ['customer_id' => 'cus_1'],
        );

        $this->assertSame('checkout_1', $request->requestId());
        $this->assertSame(
            [
                'customer_id' => 'cus_1',
                'product_id' => 'prod_1',
                'success_url' => 'https://example.com/success',
            ],
            $request->toArray(),
        );
    }

    public function test_create_checkout_request_allows_omitting_success_url(): void
    {
        $request = new CreateCheckoutRequest(
            productId: 'prod_1',
            extra: ['units' => 2],
        );

        $this->assertNull($request->requestId());
        $this->assertSame(
            [
                'units' => 2,
                'product_id' => 'prod_1',
            ],
            $request->toArray(),
        );
    }

    public function test_create_checkout_request_throws_for_empty_required_values(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        (new CreateCheckoutRequest(
            productId: ' ',
            successUrl: 'https://example.com/success',
        ))->toArray();
    }

    public function test_create_checkout_request_throws_for_empty_success_url_when_provided(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        (new CreateCheckoutRequest(
            productId: 'prod_1',
            successUrl: ' ',
        ))->toArray();
    }

    public function test_update_and_upgrade_subscription_requests_expose_payloads(): void
    {
        $update = UpdateSubscriptionRequest::withUpdateBehavior(
            'proration-charge-immediately',
            ['plan' => 'pro'],
            'req_1',
        );
        $upgrade = new UpgradeSubscriptionRequest('prod_new', 'req_2', ['coupon' => 'DISC10']);

        $this->assertSame(
            ['plan' => 'pro', 'update_behavior' => 'proration-charge-immediately'],
            $update->toArray(),
        );
        $this->assertSame('req_1', $update->requestId());
        $this->assertSame(
            ['coupon' => 'DISC10', 'product_id' => 'prod_new'],
            $upgrade->toArray(),
        );
        $this->assertSame('req_2', $upgrade->requestId());
    }

    public function test_update_subscription_request_throws_for_unsupported_update_behavior(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        UpdateSubscriptionRequest::withUpdateBehavior('prorate');
    }

    public function test_subscription_cancel_pause_resume_requests_expose_payloads(): void
    {
        $cancel = new CancelSubscriptionRequest(
            mode: 'scheduled',
            onExecute: 'pause',
            requestId: 'cancel_req_1',
        );
        $pause = new PauseSubscriptionRequest('pause_req_1');
        $resume = new ResumeSubscriptionRequest('resume_req_1');

        $this->assertSame(
            [
                'mode' => 'scheduled',
                'onExecute' => 'pause',
            ],
            $cancel->toArray(),
        );
        $this->assertSame('cancel_req_1', $cancel->requestId());
        $this->assertSame([], $pause->toArray());
        $this->assertSame('pause_req_1', $pause->requestId());
        $this->assertSame([], $resume->toArray());
        $this->assertSame('resume_req_1', $resume->requestId());
    }

    public function test_cancel_subscription_request_validates_mode_and_on_execute_combination(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        (new CancelSubscriptionRequest(
            mode: 'immediate',
            onExecute: 'cancel',
        ))->toArray();
    }

    public function test_create_product_request_builds_payload_and_validates_required_values(): void
    {
        $request = new CreateProductRequest(
            name: 'Pro Plan',
            price: 2900,
            currency: 'usd',
            billingType: 'recurring',
            billingPeriod: 'every-month',
            requestId: 'prod_req_1',
            extra: ['tax_mode' => 'exclusive'],
        );

        $this->assertSame('prod_req_1', $request->requestId());
        $this->assertSame(
            [
                'tax_mode' => 'exclusive',
                'name' => 'Pro Plan',
                'price' => 2900,
                'currency' => 'USD',
                'billing_type' => 'recurring',
                'billing_period' => 'every-month',
            ],
            $request->toArray(),
        );
    }

    public function test_create_product_request_throws_for_invalid_recurring_configuration(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        (new CreateProductRequest(
            name: 'Pro Plan',
            price: 2900,
            currency: 'USD',
            billingType: 'recurring',
            billingPeriod: null,
        ))->toArray();
    }

    public function test_create_discount_request_builds_percentage_and_fixed_payloads(): void
    {
        $percentage = new CreateDiscountRequest(
            name: 'Launch Promo',
            type: 'percentage',
            duration: 'repeating',
            appliesToProducts: ['prod_1'],
            percentage: 25,
            durationInMonths: 3,
            requestId: 'disc_req_1',
        );

        $fixed = new CreateDiscountRequest(
            name: 'Fixed Promo',
            type: 'fixed',
            duration: 'once',
            appliesToProducts: ['prod_2'],
            amount: 1000,
            currency: 'usd',
            code: 'FIXED10',
            maxRedemptions: 100,
        );

        $this->assertSame('disc_req_1', $percentage->requestId());
        $this->assertSame(
            [
                'name' => 'Launch Promo',
                'type' => 'percentage',
                'duration' => 'repeating',
                'applies_to_products' => ['prod_1'],
                'percentage' => 25,
                'duration_in_months' => 3,
            ],
            $percentage->toArray(),
        );
        $this->assertSame(
            [
                'name' => 'Fixed Promo',
                'type' => 'fixed',
                'duration' => 'once',
                'applies_to_products' => ['prod_2'],
                'code' => 'FIXED10',
                'amount' => 1000,
                'currency' => 'USD',
                'max_redemptions' => 100,
            ],
            $fixed->toArray(),
        );
    }

    public function test_create_discount_request_throws_for_invalid_fixed_configuration(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        (new CreateDiscountRequest(
            name: 'Broken Fixed',
            type: 'fixed',
            duration: 'once',
            appliesToProducts: ['prod_1'],
            amount: 1000,
            currency: null,
        ))->toArray();
    }

    public function test_license_request_payloads_are_built_with_expected_fields(): void
    {
        $activate = new ActivateLicenseRequest('KEY_1', 'macbook-pro', 'license_req_1');
        $validate = new ValidateLicenseRequest('KEY_1', 'inst_1');
        $deactivate = new DeactivateLicenseRequest('KEY_1', 'inst_1');

        $this->assertSame(
            ['key' => 'KEY_1', 'instance_name' => 'macbook-pro'],
            $activate->toArray(),
        );
        $this->assertSame('license_req_1', $activate->requestId());
        $this->assertSame(
            ['key' => 'KEY_1', 'instance_id' => 'inst_1'],
            $validate->toArray(),
        );
        $this->assertSame(
            ['key' => 'KEY_1', 'instance_id' => 'inst_1'],
            $deactivate->toArray(),
        );
    }

    public function test_create_billing_link_request_builds_payload_and_validates_customer_id(): void
    {
        $request = new CreateBillingLinkRequest(
            customerId: 'cus_1',
            requestId: 'billing_req_1',
        );

        $this->assertSame(['customer_id' => 'cus_1'], $request->toArray());
        $this->assertSame('billing_req_1', $request->requestId());
    }

    public function test_create_billing_link_request_throws_for_empty_customer_id(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        (new CreateBillingLinkRequest(
            customerId: ' ',
        ))->toArray();
    }
}
