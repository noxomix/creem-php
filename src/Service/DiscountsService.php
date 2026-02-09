<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Service;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Http\RequestOptions;
use Noxomix\CreemPhp\Request\Discounts\CreateDiscountRequest;
use Noxomix\CreemPhp\Resource\DiscountResource;

final class DiscountsService
{
    public function __construct(
        private readonly CreemClient $client,
    ) {
    }

    /**
     * @param CreateDiscountRequest $payload
     * @return DiscountResource
     */
    public function create(CreateDiscountRequest $payload): DiscountResource
    {
        return new DiscountResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: '/v1/discounts',
            body: $payload->toArray(),
            requestId: $payload->requestId(),
        )));
    }

    /**
     * @param string|null $discountId
     * @param string|null $discountCode
     * @return DiscountResource
     */
    public function retrieve(?string $discountId = null, ?string $discountCode = null): DiscountResource
    {
        $normalizedDiscountId = $discountId === null ? null : trim($discountId);
        $normalizedDiscountCode = $discountCode === null ? null : trim($discountCode);

        if ($normalizedDiscountId === '' || $normalizedDiscountCode === '') {
            throw new InvalidConfigurationException('discountId and discountCode must not be empty when provided.');
        }

        if ($normalizedDiscountId === null && $normalizedDiscountCode === null) {
            throw new InvalidConfigurationException('Either discountId or discountCode must be provided.');
        }

        if ($normalizedDiscountId !== null && $normalizedDiscountCode !== null) {
            throw new InvalidConfigurationException('Provide either discountId or discountCode, not both.');
        }

        $query = $normalizedDiscountId !== null
            ? ['discount_id' => $normalizedDiscountId]
            : ['discount_code' => $normalizedDiscountCode];

        return new DiscountResource($this->client->request(new RequestOptions(
            method: 'GET',
            path: '/v1/discounts',
            query: $query,
        )));
    }

    /**
     * @param string $discountId
     * @return void
     */
    public function delete(string $discountId): void
    {
        $normalizedDiscountId = trim($discountId);

        if ($normalizedDiscountId === '') {
            throw new InvalidConfigurationException('discountId must not be empty.');
        }

        $this->client->request(new RequestOptions(
            method: 'DELETE',
            path: sprintf('/v1/discounts/%s/delete', $normalizedDiscountId),
        ));
    }
}
