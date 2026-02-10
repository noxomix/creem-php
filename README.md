# Creem PHP

[![Unit Tests](https://github.com/noxomix/creem-php/actions/workflows/unit-tests.yml/badge.svg)](https://github.com/noxomix/creem-php/actions/workflows/unit-tests.yml)

`noxomix/creem-php` is a framework-agnostic core SDK for Creem integrations.

## Installation

```bash
composer require noxomix/creem-php
```

## Usage

```php
<?php

use Noxomix\CreemPhp\Config\EnvMode;
use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Discount\DiscountDuration;
use Noxomix\CreemPhp\Product\BillingPeriod;
use Noxomix\CreemPhp\Product\BillingType;
use Noxomix\CreemPhp\Product\TaxCategory;
use Noxomix\CreemPhp\Product\TaxMode;
use Noxomix\CreemPhp\Subscription\SubscriptionStatus;

$client = new CreemClient([
    'api_key' => 'creem_your_api_key',
    'mode' => EnvMode::TEST, // default
]);

$checkout = $client->checkouts()->create(
    productId: 'prod_123',
    discountCode: 'LAUNCH50', // optional
    customer: ['email' => 'user@example.ee'], // optional prefill
    metadata: ['source' => 'readme'], // optional
);

$oneTimeProduct = $client->products()->create(
    name: 'Starter Plan',
    price: 1900,
    currency: 'EUR',
    billingType: BillingType::ONETIME,
);

$recurringProduct = $client->products()->create(
    name: 'Pro Monthly',
    price: 2900,
    currency: 'EUR',
    billingType: BillingType::RECURRING,
    billingPeriod: BillingPeriod::EVERY_MONTH,
    taxMode: TaxMode::EXCLUSIVE,
    taxCategory: TaxCategory::SAAS,
    defaultSuccessUrl: 'https://example.com/success',
);

$customRecurringProduct = $client->products()->create(
    name: 'Quarterly Plan',
    price: 7900,
    currency: 'EUR',
    billingType: BillingType::RECURRING,
    billingPeriod: BillingPeriod::EVERY_THREE_MONTHS,
);

$customCycleProduct = $client->products()->create(
    name: 'Custom Cycle Plan',
    price: 4900,
    currency: 'EUR',
    billingType: BillingType::RECURRING,
    billingPeriod: 'every-quarter',
);

$percentageDiscount = $client->discounts()->create(
    name: 'Launch 20',
    type: 'percentage',
    duration: DiscountDuration::ONCE,
    appliesToProducts: ['prod_123'],
    percentage: 20,
);

$repeatingDiscount = $client->discounts()->create(
    name: 'First 3 Months',
    type: 'percentage',
    duration: DiscountDuration::REPEATING,
    appliesToProducts: ['prod_123'],
    percentage: 15,
    durationInMonths: 3,
);

// Load one subscription by ID
$subscription = $client->subscriptions()->retrieve('sub_123');

// Fetch first customer page (defaults: page 1, size 50)
$customers = $client->customers()->list();

 // Fetch one customer by email
$customerByEmail = $client->customers()->retrieve(email: 'user@example.ee');

// Search transactions with default query
$transactions = $client->transactions()->search();

if ($subscription->status() === SubscriptionStatus::ACTIVE) {
    $client->subscriptions()->cancel('sub_123');
}
```

For `BillingType::ONETIME`, you can omit `billingPeriod` entirely, or set `BillingPeriod::ONCE` explicitly.
For recurring products, preferred enum values are `EVERY_MONTH`, `EVERY_THREE_MONTHS`, `EVERY_SIX_MONTHS`, `EVERY_YEAR`.

## Webhooks

```php
<?php

use Noxomix\CreemPhp\Webhook\Dispatch\DefaultWebhookDispatcher;
use Noxomix\CreemPhp\Webhook\WebhookProcessor;

$processor = new WebhookProcessor(
    webhookSecret: '<webhook secret>',
    dispatcher: new DefaultWebhookDispatcher([
        'checkout.completed' => static function ($event): void {
            // Provision initial access
        },
        'subscription.paid' => static function ($event): void {
            // Continue subscription access
        },
        'subscription.canceled' => static function ($event): void {
            // Revoke subscription access
        },
    ]),
);

$result = $processor->process($rawJsonBody, $headers['creem-signature'] ?? null);
```

## Notes

- Guzzle HTTP.
- Error diagnostics preserve `trace_id`, `status`, `error`, and `message` values when present.
- Root client exposes domain services: `checkouts`, `subscriptions`, `customers`, `transactions`, `products`, `discounts`, `licenses`.
- Enums resolve to API strings, but string input remains supported (`EnvMode`, `BillingType`, `BillingPeriod`, `TaxMode`, `TaxCategory`, `DiscountDuration`).
- Webhook processing includes signature verification, parsing, dispatching, and duplicate-event protection.
