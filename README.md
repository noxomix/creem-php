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
use Noxomix\CreemPhp\Request\Checkouts\CreateCheckoutRequest;
use Noxomix\CreemPhp\Subscription\SubscriptionStatus;

$config = CreemConfig::fromApiKey(
    apiKey: 'creem_your_api_key',
    mode: 'test',
);

$client = new CreemClient($config);

$checkout = $client->checkouts()->create(new CreateCheckoutRequest(
    productId: 'prod_123',
));

$subscription = $client->subscriptions()->retrieve('sub_123');
$customers = $client->customers()->list();
$customerByEmail = $client->customers()->retrieveByEmail('user@example.com');
$transactions = $client->transactions()->search();

if ($subscription->status() === SubscriptionStatus::ACTIVE) {
    $client->subscriptions()->cancel('sub_123');
}
```

## Webhooks

```php
<?php

declare(strict_types=1);

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
- Logging contract is PSR-3 with `NullLogger` default.
- Error diagnostics preserve `trace_id`, `status`, `error`, and `message` values when present.
- Root client exposes domain services: `checkouts`, `subscriptions`, `customers`, `transactions`, `products`, `discounts`, `licenses`.
- Prefer `request(new RequestOptions(...))` over positional `rawRequest(...)` when using raw endpoints.
- Webhook processing includes signature verification, parsing, dispatching, and duplicate-event protection.
