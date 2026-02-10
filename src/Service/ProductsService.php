<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Service;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Http\RequestOptions;
use Noxomix\CreemPhp\Pagination\PaginatedResponse;
use Noxomix\CreemPhp\Pagination\PaginationExtractor;
use Noxomix\CreemPhp\Product\BillingPeriod;
use Noxomix\CreemPhp\Product\BillingType;
use Noxomix\CreemPhp\Resource\ProductResource;

final class ProductsService
{
    public function __construct(
        private readonly CreemClient $client,
    ) {
    }

    public function create(
        string $name,
        int $price,
        string $currency,
        BillingType|string $billingType,
        BillingPeriod|string|null $billingPeriod = null,
        ?string $requestId = null,
    ): ProductResource {
        $normalizedName = trim($name);
        $normalizedCurrency = strtoupper(trim($currency));
        $normalizedBillingType = BillingType::fromInput($billingType)->value;

        if ($normalizedName === '') {
            throw new InvalidConfigurationException('name must not be empty.');
        }

        if ($price < 100) {
            throw new InvalidConfigurationException('price must be at least 100 cents.');
        }

        if ($normalizedCurrency === '') {
            throw new InvalidConfigurationException('currency must not be empty.');
        }

        $body = [
            'name' => $normalizedName,
            'price' => $price,
            'currency' => $normalizedCurrency,
            'billing_type' => $normalizedBillingType,
        ];

        if ($normalizedBillingType === BillingType::RECURRING->value) {
            if ($billingPeriod === null) {
                throw new InvalidConfigurationException('billingPeriod must not be empty for recurring products.');
            }

            $body['billing_period'] = BillingPeriod::toValue($billingPeriod);
        }

        if ($normalizedBillingType === BillingType::ONETIME->value && $billingPeriod !== null) {
            $body['billing_period'] = BillingPeriod::toValue($billingPeriod);
        }

        return new ProductResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: '/v1/products',
            body: $body,
            requestId: $requestId,
        )));
    }

    public function retrieve(string $productId): ProductResource
    {
        $normalizedProductId = trim($productId);

        if ($normalizedProductId === '') {
            throw new InvalidConfigurationException('productId must not be empty.');
        }

        return new ProductResource($this->client->request(new RequestOptions(
            method: 'GET',
            path: '/v1/products',
            query: ['product_id' => $normalizedProductId],
        )));
    }

    /**
     * @param array<string, mixed> $query
     */
    public function search(int $pageNumber = 1, int $pageSize = 50, array $query = []): PaginatedResponse
    {
        if ($pageNumber <= 0) {
            throw new InvalidConfigurationException('pageNumber must be greater than 0.');
        }

        if ($pageSize <= 0) {
            throw new InvalidConfigurationException('pageSize must be greater than 0.');
        }

        $payload = $this->client->request(new RequestOptions(
            method: 'GET',
            path: '/v1/products/search',
            query: array_merge(
                $query,
                [
                    'page_number' => $pageNumber,
                    'page_size' => $pageSize,
                ],
            ),
        ));

        return new PaginatedResponse(
            payload: $payload,
            pagination: PaginationExtractor::fromPayload($payload),
        );
    }
}
