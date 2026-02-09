<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Service;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Http\RequestOptions;
use Noxomix\CreemPhp\Request\Subscriptions\CancelSubscriptionRequest;
use Noxomix\CreemPhp\Request\Subscriptions\PauseSubscriptionRequest;
use Noxomix\CreemPhp\Request\Subscriptions\ResumeSubscriptionRequest;
use Noxomix\CreemPhp\Request\Subscriptions\UpdateSubscriptionRequest;
use Noxomix\CreemPhp\Request\Subscriptions\UpgradeSubscriptionRequest;
use Noxomix\CreemPhp\Resource\SubscriptionResource;

final class SubscriptionsService
{
    public function __construct(
        private readonly CreemClient $client,
    ) {
    }

    /**
     * @param string $subscriptionId
     * @return SubscriptionResource
     */
    public function retrieve(string $subscriptionId): SubscriptionResource
    {
        $normalizedSubscriptionId = trim($subscriptionId);

        if ($normalizedSubscriptionId === '') {
            throw new InvalidConfigurationException('subscriptionId must not be empty.');
        }

        return new SubscriptionResource($this->client->request(new RequestOptions(
            method: 'GET',
            path: '/v1/subscriptions',
            query: ['subscription_id' => $normalizedSubscriptionId],
        )));
    }

    /**
     * @param string $subscriptionId
     * @return SubscriptionResource
     */
    public function reconcile(string $subscriptionId): SubscriptionResource
    {
        return $this->retrieve($subscriptionId);
    }

    /**
     * @param string $subscriptionId
     * @param UpdateSubscriptionRequest $payload
     * @return SubscriptionResource
     */
    public function update(
        string $subscriptionId,
        UpdateSubscriptionRequest $payload,
    ): SubscriptionResource
    {
        return new SubscriptionResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: sprintf('/v1/subscriptions/%s', $this->normalizeSubscriptionId($subscriptionId)),
            body: $payload->toArray(),
            requestId: $payload->requestId(),
        )));
    }

    /**
     * @param string $subscriptionId
     * @param UpgradeSubscriptionRequest $payload
     * @return SubscriptionResource
     */
    public function upgrade(
        string $subscriptionId,
        UpgradeSubscriptionRequest $payload,
    ): SubscriptionResource
    {
        return new SubscriptionResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: sprintf('/v1/subscriptions/%s/upgrade', $this->normalizeSubscriptionId($subscriptionId)),
            body: $payload->toArray(),
            requestId: $payload->requestId(),
        )));
    }

    /**
     * @param string $subscriptionId
     * @param CancelSubscriptionRequest|null $payload
     * @return SubscriptionResource
     */
    public function cancel(
        string $subscriptionId,
        ?CancelSubscriptionRequest $payload = null,
    ): SubscriptionResource
    {
        $requestPayload = $payload ?? new CancelSubscriptionRequest();

        return new SubscriptionResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: sprintf('/v1/subscriptions/%s/cancel', $this->normalizeSubscriptionId($subscriptionId)),
            body: $requestPayload->toArray(),
            requestId: $requestPayload->requestId(),
        )));
    }

    /**
     * @param string $subscriptionId
     * @param PauseSubscriptionRequest|null $payload
     * @return SubscriptionResource
     */
    public function pause(string $subscriptionId, ?PauseSubscriptionRequest $payload = null): SubscriptionResource
    {
        $requestPayload = $payload ?? new PauseSubscriptionRequest();

        return new SubscriptionResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: sprintf('/v1/subscriptions/%s/pause', $this->normalizeSubscriptionId($subscriptionId)),
            body: $requestPayload->toArray(),
            requestId: $requestPayload->requestId(),
        )));
    }

    /**
     * @param string $subscriptionId
     * @param ResumeSubscriptionRequest|null $payload
     * @return SubscriptionResource
     */
    public function resume(string $subscriptionId, ?ResumeSubscriptionRequest $payload = null): SubscriptionResource
    {
        $requestPayload = $payload ?? new ResumeSubscriptionRequest();

        return new SubscriptionResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: sprintf('/v1/subscriptions/%s/resume', $this->normalizeSubscriptionId($subscriptionId)),
            body: $requestPayload->toArray(),
            requestId: $requestPayload->requestId(),
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
}
