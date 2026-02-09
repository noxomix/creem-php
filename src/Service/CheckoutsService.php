<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Service;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Http\RequestOptions;
use Noxomix\CreemPhp\Request\Checkouts\CreateCheckoutRequest;
use Noxomix\CreemPhp\Resource\CheckoutResource;

final class CheckoutsService
{
    public function __construct(
        private readonly CreemClient $client,
    ) {
    }

    /**
     * @param CreateCheckoutRequest $payload
     * @return CheckoutResource
     */
    public function create(CreateCheckoutRequest $payload): CheckoutResource
    {
        return new CheckoutResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: '/v1/checkouts',
            body: $payload->toArray(),
            requestId: $payload->requestId(),
        )));
    }

    /**
     * @param string $checkoutId
     * @return CheckoutResource
     */
    public function retrieve(string $checkoutId): CheckoutResource
    {
        $normalizedCheckoutId = trim($checkoutId);

        if ($normalizedCheckoutId === '') {
            throw new InvalidConfigurationException('checkoutId must not be empty.');
        }

        return new CheckoutResource($this->client->request(new RequestOptions(
            method: 'GET',
            path: '/v1/checkouts',
            query: ['checkout_id' => $normalizedCheckoutId],
        )));
    }
}
