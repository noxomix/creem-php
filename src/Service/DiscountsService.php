<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Service;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Discount\DiscountDuration;
use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Http\RequestOptions;
use Noxomix\CreemPhp\Resource\DiscountResource;

final class DiscountsService
{
    public function __construct(
        private readonly CreemClient $client,
    ) {
    }

    /**
     * @param array<int, string> $appliesToProducts
     */
    public function create(
        string $name,
        string $type,
        DiscountDuration|string $duration,
        array $appliesToProducts,
        ?int $percentage = null,
        ?int $amount = null,
        ?string $currency = null,
        ?string $code = null,
        ?int $durationInMonths = null,
        ?int $maxRedemptions = null,
        ?string $expiryDate = null,
        ?string $requestId = null,
    ): DiscountResource {
        $normalizedName = trim($name);
        $normalizedType = strtolower(trim($type));
        $normalizedDuration = DiscountDuration::fromInput($duration)->value;
        $normalizedProducts = $this->normalizeProducts($appliesToProducts);

        if ($normalizedName === '') {
            throw new InvalidConfigurationException('name must not be empty.');
        }

        if (! in_array($normalizedType, ['percentage', 'fixed'], true)) {
            throw new InvalidConfigurationException('type must be "percentage" or "fixed".');
        }

        if ($normalizedProducts === []) {
            throw new InvalidConfigurationException('appliesToProducts must include at least one product ID.');
        }

        $body = [
            'name' => $normalizedName,
            'type' => $normalizedType,
            'duration' => $normalizedDuration,
            'applies_to_products' => $normalizedProducts,
        ];

        if ($code !== null) {
            $normalizedCode = trim($code);

            if ($normalizedCode !== '') {
                $body['code'] = $normalizedCode;
            }
        }

        if ($normalizedType === 'percentage') {
            if ($percentage === null || $percentage <= 0) {
                throw new InvalidConfigurationException('percentage must be greater than 0 for percentage discounts.');
            }

            $body['percentage'] = $percentage;
        }

        if ($normalizedType === 'fixed') {
            if ($amount === null || $amount <= 0) {
                throw new InvalidConfigurationException('amount must be greater than 0 for fixed discounts.');
            }

            $normalizedCurrency = $currency === null ? '' : strtoupper(trim($currency));

            if ($normalizedCurrency === '') {
                throw new InvalidConfigurationException('currency must not be empty for fixed discounts.');
            }

            $body['amount'] = $amount;
            $body['currency'] = $normalizedCurrency;
        }

        if ($normalizedDuration === DiscountDuration::REPEATING->value) {
            if ($durationInMonths === null || $durationInMonths <= 0) {
                throw new InvalidConfigurationException('durationInMonths must be greater than 0 for repeating discounts.');
            }

            $body['duration_in_months'] = $durationInMonths;
        }

        if ($maxRedemptions !== null) {
            if ($maxRedemptions <= 0) {
                throw new InvalidConfigurationException('maxRedemptions must be greater than 0 when provided.');
            }

            $body['max_redemptions'] = $maxRedemptions;
        }

        if ($expiryDate !== null) {
            $normalizedExpiryDate = trim($expiryDate);

            if ($normalizedExpiryDate === '') {
                throw new InvalidConfigurationException('expiryDate must not be empty when provided.');
            }

            $body['expiry_date'] = $normalizedExpiryDate;
        }

        return new DiscountResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: '/v1/discounts',
            body: $body,
            requestId: $requestId,
        )));
    }

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

    /**
     * @param array<int, string> $products
     * @return array<int, string>
     */
    private function normalizeProducts(array $products): array
    {
        $normalized = [];

        foreach ($products as $productId) {
            $trimmed = trim($productId);

            if ($trimmed !== '') {
                $normalized[] = $trimmed;
            }
        }

        return $normalized;
    }
}
