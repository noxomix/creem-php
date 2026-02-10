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
use Noxomix\CreemPhp\Product\TaxCategory;
use Noxomix\CreemPhp\Product\TaxMode;
use Noxomix\CreemPhp\Resource\ProductResource;

final class ProductsService
{
    public function __construct(
        private readonly CreemClient $client,
    ) {
    }

    /**
     * @param BillingType|string $billingType
     * @param BillingPeriod|string|null $billingPeriod
     * @param TaxMode|string|null $taxMode
     * @param TaxCategory|string|null $taxCategory
     * @param list<array{
     *     type: string,
     *     key: string,
     *     label: string,
     *     optional?: bool,
     *     text?: array<string, mixed>,
     *     checkbox?: array<string, mixed>
     * }>|null $customFields
     */
    public function create(
        string $name,
        int $price,
        string $currency,
        BillingType|string $billingType,
        BillingPeriod|string|null $billingPeriod = null,
        ?string $requestId = null,
        ?string $description = null,
        ?string $imageUrl = null,
        TaxMode|string|null $taxMode = null,
        TaxCategory|string|null $taxCategory = null,
        ?string $defaultSuccessUrl = null,
        ?array $customFields = null,
        ?bool $abandonedCartRecoveryEnabled = null,
    ): ProductResource {
        $normalizedName = trim($name);
        $normalizedCurrency = strtoupper(trim($currency));
        $normalizedBillingType = BillingType::fromInput($billingType)->value;
        $normalizedDescription = $this->normalizeOptionalString($description, 'description');
        $normalizedImageUrl = $this->normalizeOptionalUrl($imageUrl, 'imageUrl');
        $normalizedTaxMode = $taxMode === null ? null : TaxMode::fromInput($taxMode)->value;
        $normalizedTaxCategory = $taxCategory === null ? null : TaxCategory::fromInput($taxCategory)->value;
        $normalizedDefaultSuccessUrl = $this->normalizeOptionalUrl($defaultSuccessUrl, 'defaultSuccessUrl');
        $normalizedCustomFields = $this->normalizeCustomFields($customFields);

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

        if ($normalizedDescription !== null) {
            $body['description'] = $normalizedDescription;
        }

        if ($normalizedImageUrl !== null) {
            $body['image_url'] = $normalizedImageUrl;
        }

        if ($normalizedTaxMode !== null) {
            $body['tax_mode'] = $normalizedTaxMode;
        }

        if ($normalizedTaxCategory !== null) {
            $body['tax_category'] = $normalizedTaxCategory;
        }

        if ($normalizedDefaultSuccessUrl !== null) {
            $body['default_success_url'] = $normalizedDefaultSuccessUrl;
        }

        if ($normalizedCustomFields !== null) {
            $body['custom_fields'] = $normalizedCustomFields;
        }

        if ($abandonedCartRecoveryEnabled !== null) {
            $body['abandoned_cart_recovery_enabled'] = $abandonedCartRecoveryEnabled;
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

    private function normalizeOptionalString(?string $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            throw new InvalidConfigurationException(sprintf('%s must not be empty when provided.', $field));
        }

        return $normalized;
    }

    private function normalizeOptionalUrl(?string $value, string $field): ?string
    {
        $normalized = $this->normalizeOptionalString($value, $field);

        if ($normalized === null) {
            return null;
        }

        if (filter_var($normalized, FILTER_VALIDATE_URL) === false) {
            throw new InvalidConfigurationException(sprintf('%s must be a valid URL when provided.', $field));
        }

        return $normalized;
    }

    /**
     * @param list<array{
     *     type: string,
     *     key: string,
     *     label: string,
     *     optional?: bool,
     *     text?: array<string, mixed>,
     *     checkbox?: array<string, mixed>
     * }>|null $customFields
     * @return list<array{
     *     type: string,
     *     key: string,
     *     label: string,
     *     optional?: bool,
     *     text?: array<string, mixed>,
     *     checkbox?: array<string, mixed>
     * }>|null
     */
    private function normalizeCustomFields(?array $customFields): ?array
    {
        if ($customFields === null) {
            return null;
        }

        if (count($customFields) > 3) {
            throw new InvalidConfigurationException('customFields supports a maximum of 3 fields.');
        }

        $normalized = [];

        foreach ($customFields as $index => $field) {
            if (! is_array($field)) {
                throw new InvalidConfigurationException(sprintf('customFields[%d] must be an array.', $index));
            }

            $type = isset($field['type']) && is_string($field['type'])
                ? strtolower(trim($field['type']))
                : '';
            $key = isset($field['key']) && is_string($field['key'])
                ? trim($field['key'])
                : '';
            $label = isset($field['label']) && is_string($field['label'])
                ? trim($field['label'])
                : '';

            if (! in_array($type, ['text', 'checkbox'], true)) {
                throw new InvalidConfigurationException(sprintf('customFields[%d].type must be "text" or "checkbox".', $index));
            }

            if ($key === '') {
                throw new InvalidConfigurationException(sprintf('customFields[%d].key must not be empty.', $index));
            }

            if ($label === '') {
                throw new InvalidConfigurationException(sprintf('customFields[%d].label must not be empty.', $index));
            }

            $normalizedField = [
                'type' => $type,
                'key' => $key,
                'label' => $label,
            ];

            if (array_key_exists('optional', $field)) {
                if (! is_bool($field['optional'])) {
                    throw new InvalidConfigurationException(sprintf('customFields[%d].optional must be a boolean when provided.', $index));
                }

                $normalizedField['optional'] = $field['optional'];
            }

            if (array_key_exists('text', $field)) {
                if (! is_array($field['text'])) {
                    throw new InvalidConfigurationException(sprintf('customFields[%d].text must be an object when provided.', $index));
                }

                if ($type !== 'text') {
                    throw new InvalidConfigurationException(sprintf('customFields[%d].text is only valid for type "text".', $index));
                }

                $normalizedField['text'] = $field['text'];
            }

            if (array_key_exists('checkbox', $field)) {
                if (! is_array($field['checkbox'])) {
                    throw new InvalidConfigurationException(sprintf('customFields[%d].checkbox must be an object when provided.', $index));
                }

                if ($type !== 'checkbox') {
                    throw new InvalidConfigurationException(sprintf('customFields[%d].checkbox is only valid for type "checkbox".', $index));
                }

                $normalizedField['checkbox'] = $field['checkbox'];
            }

            $normalized[] = $normalizedField;
        }

        return $normalized;
    }
}
