<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Service;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Http\RequestOptions;
use Noxomix\CreemPhp\Pagination\PaginatedResponse;
use Noxomix\CreemPhp\Pagination\PaginationExtractor;
use Noxomix\CreemPhp\Request\Products\CreateProductRequest;
use Noxomix\CreemPhp\Resource\ProductResource;

final class ProductsService
{
    public function __construct(
        private readonly CreemClient $client,
    ) {
    }

    /**
     * @param CreateProductRequest $payload
     * @return ProductResource
     */
    public function create(CreateProductRequest $payload): ProductResource
    {
        return new ProductResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: '/v1/products',
            body: $payload->toArray(),
            requestId: $payload->requestId(),
        )));
    }

    /**
     * @param string $productId
     * @return ProductResource
     */
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
     * @return PaginatedResponse
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
