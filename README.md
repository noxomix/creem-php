# Creem PHP

`noxomix/creem-php` is a framework-agnostic core SDK for Creem integrations.

## Installation

```bash
composer require noxomix/creem-php
```

## Usage

```php
<?php

declare(strict_types=1);

use Noxomix\CreemPhp\Config\CreemConfig;
use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Http\RequestOptions;
use Noxomix\CreemPhp\Request\Checkouts\CreateCheckoutRequest;
use Noxomix\CreemPhp\Request\Discounts\CreateDiscountRequest;
use Noxomix\CreemPhp\Request\Licenses\ActivateLicenseRequest;
use Noxomix\CreemPhp\Request\Products\CreateProductRequest;
use Noxomix\CreemPhp\Request\Subscriptions\UpdateSubscriptionRequest;
use Noxomix\CreemPhp\Subscription\SubscriptionStatus;

$config = CreemConfig::fromApiKey(
    apiKey: 'creem_your_api_key',
    mode: 'test',
    options: [
        'connect_timeout' => 10.0,
        'request_timeout' => 30.0,
        'max_retries' => 3,
        'retry_base_delay_ms' => 1000,
        'retry_max_delay_ms' => 4000,
    ],
);

$client = new CreemClient($config);

$checkout = $client->checkouts()->create(
    payload: new CreateCheckoutRequest(
        productId: 'prod_123',
        successUrl: 'https://example.com/success',
        requestId: 'order_123',
    ),
);

$subscription = $client->subscriptions()->retrieve('sub_123');

if ($subscription->status() === SubscriptionStatus::ACTIVE) {
    $client->subscriptions()->update(
        'sub_123',
        UpdateSubscriptionRequest::withUpdateBehavior('proration-charge-immediately'),
    );
}

$customers = $client->customers()->list(pageNumber: 1, pageSize: 50);
$pagination = $customers->pagination();
$customerByEmail = $client->customers()->retrieveByEmail('user@example.com');

$product = $client->products()->create(new CreateProductRequest(
    name: 'Pro Plan',
    price: 2900,
    currency: 'USD',
    billingType: 'recurring',
    billingPeriod: 'every-month',
));

$discount = $client->discounts()->create(new CreateDiscountRequest(
    name: 'Launch Promo',
    type: 'percentage',
    duration: 'once',
    appliesToProducts: ['prod_123'],
    percentage: 25,
));

$license = $client->licenses()->activate(new ActivateLicenseRequest(
    key: 'ABC123-XYZ456-XYZ456-XYZ456',
    instanceName: 'production-server-1',
));

$raw = $client->request(new RequestOptions(
    method: 'GET',
    path: '/v1/transactions/search',
    query: ['page_number' => 1, 'page_size' => 20],
));
```

## Webhooks

```php
<?php

declare(strict_types=1);

use Noxomix\CreemPhp\Webhook\Dispatch\DefaultWebhookDispatcher;
use Noxomix\CreemPhp\Webhook\WebhookProcessor;

$processor = new WebhookProcessor(
    webhookSecret: 'whsec_your_webhook_secret',
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

- V1 transport uses Guzzle.
- Logging contract is PSR-3 with `NullLogger` default.
- Error diagnostics preserve `trace_id`, `status`, `error`, and `message` values when present.
- Root client exposes domain services: `checkouts`, `subscriptions`, `customers`, `transactions`, `products`, `discounts`, `licenses`.
- Prefer `request(new RequestOptions(...))` over positional `rawRequest(...)` when using raw endpoints.
- Webhook processing includes signature verification, parsing, dispatching, and duplicate-event protection.
- Default webhook idempotency storage is in-memory and process-local; production multi-worker setups should inject a persistent `WebhookIdempotencyStoreInterface` implementation with atomic `claim` semantics.

## Governance

- Constitution: `CONSTITUTION.md`
