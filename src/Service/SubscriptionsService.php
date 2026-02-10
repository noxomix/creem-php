<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Service;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Http\RequestOptions;
use Noxomix\CreemPhp\Resource\SubscriptionResource;

final class SubscriptionsService
{
    /** @var array<int, string> */
    private const ALLOWED_UPDATE_BEHAVIORS = [
        'proration-charge-immediately',
        'proration-charge',
        'proration-none',
    ];

    public function __construct(
        private readonly CreemClient $client,
    ) {
    }

    public function retrieve(string $subscriptionId): SubscriptionResource
    {
        $normalizedSubscriptionId = $this->normalizeSubscriptionId($subscriptionId);

        return new SubscriptionResource($this->client->request(new RequestOptions(
            method: 'GET',
            path: '/v1/subscriptions',
            query: ['subscription_id' => $normalizedSubscriptionId],
        )));
    }

    public function reconcile(string $subscriptionId): SubscriptionResource
    {
        return $this->retrieve($subscriptionId);
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    public function update(
        string $subscriptionId,
        string $updateBehavior = 'proration-charge',
        array $items = [],
        ?string $requestId = null,
    ): SubscriptionResource {
        $normalizedUpdateBehavior = $this->normalizeUpdateBehavior($updateBehavior);
        $body = [
            'update_behavior' => $normalizedUpdateBehavior,
        ];

        if ($items !== []) {
            $body['items'] = $items;
        }

        return new SubscriptionResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: sprintf('/v1/subscriptions/%s', $this->normalizeSubscriptionId($subscriptionId)),
            body: $body,
            requestId: $requestId,
        )));
    }

    public function upgrade(
        string $subscriptionId,
        string $productId,
        string $updateBehavior = 'proration-charge-immediately',
        ?string $requestId = null,
    ): SubscriptionResource {
        $normalizedProductId = trim($productId);
        $normalizedUpdateBehavior = $this->normalizeUpdateBehavior($updateBehavior);

        if ($normalizedProductId === '') {
            throw new InvalidConfigurationException('productId must not be empty.');
        }

        return new SubscriptionResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: sprintf('/v1/subscriptions/%s/upgrade', $this->normalizeSubscriptionId($subscriptionId)),
            body: [
                'product_id' => $normalizedProductId,
                'update_behavior' => $normalizedUpdateBehavior,
            ],
            requestId: $requestId,
        )));
    }

    public function cancel(
        string $subscriptionId,
        ?string $mode = null,
        ?string $onExecute = null,
        ?string $requestId = null,
    ): SubscriptionResource {
        $body = [];
        $normalizedMode = $this->normalizeNullable($mode);
        $normalizedOnExecute = $this->normalizeNullable($onExecute);

        if ($normalizedMode !== null) {
            if (! in_array($normalizedMode, ['immediate', 'scheduled'], true)) {
                throw new InvalidConfigurationException('mode must be "immediate" or "scheduled" when provided.');
            }

            $body['mode'] = $normalizedMode;
        }

        if ($normalizedOnExecute !== null) {
            if (! in_array($normalizedOnExecute, ['cancel', 'pause'], true)) {
                throw new InvalidConfigurationException('onExecute must be "cancel" or "pause" when provided.');
            }

            if ($normalizedMode !== 'scheduled') {
                throw new InvalidConfigurationException('onExecute is only valid when mode is "scheduled".');
            }

            $body['onExecute'] = $normalizedOnExecute;
        }

        return new SubscriptionResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: sprintf('/v1/subscriptions/%s/cancel', $this->normalizeSubscriptionId($subscriptionId)),
            body: $body,
            requestId: $requestId,
        )));
    }

    public function pause(string $subscriptionId, ?string $requestId = null): SubscriptionResource
    {
        return new SubscriptionResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: sprintf('/v1/subscriptions/%s/pause', $this->normalizeSubscriptionId($subscriptionId)),
            body: [],
            requestId: $requestId,
        )));
    }

    public function resume(string $subscriptionId, ?string $requestId = null): SubscriptionResource
    {
        return new SubscriptionResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: sprintf('/v1/subscriptions/%s/resume', $this->normalizeSubscriptionId($subscriptionId)),
            body: [],
            requestId: $requestId,
        )));
    }

    private function normalizeSubscriptionId(string $subscriptionId): string
    {
        $normalizedSubscriptionId = trim($subscriptionId);

        if ($normalizedSubscriptionId === '') {
            throw new InvalidConfigurationException('subscriptionId must not be empty.');
        }

        return $normalizedSubscriptionId;
    }

    private function normalizeNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }

    private function normalizeUpdateBehavior(string $updateBehavior): string
    {
        $normalized = strtolower(trim($updateBehavior));

        if (! in_array($normalized, self::ALLOWED_UPDATE_BEHAVIORS, true)) {
            throw new InvalidConfigurationException(sprintf(
                'updateBehavior must be one of: %s.',
                implode(', ', self::ALLOWED_UPDATE_BEHAVIORS),
            ));
        }

        return $normalized;
    }
}
