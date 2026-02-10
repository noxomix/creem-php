<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Service;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Http\RequestOptions;
use Noxomix\CreemPhp\Resource\CheckoutResource;

final class CheckoutsService
{
    public function __construct(
        private readonly CreemClient $client,
    ) {
    }

    public function create(
        string $productId,
        ?string $successUrl = null,
        ?string $requestId = null,
    ): CheckoutResource {
        $normalizedProductId = trim($productId);

        if ($normalizedProductId === '') {
            throw new InvalidConfigurationException('productId must not be empty.');
        }

        $body = [
            'product_id' => $normalizedProductId,
        ];

        if ($successUrl !== null) {
            $normalizedSuccessUrl = trim($successUrl);

            if ($normalizedSuccessUrl === '') {
                throw new InvalidConfigurationException('successUrl must not be empty when provided.');
            }

            $body['success_url'] = $normalizedSuccessUrl;
        }

        return new CheckoutResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: '/v1/checkouts',
            body: $body,
            requestId: $requestId,
        )));
    }

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
