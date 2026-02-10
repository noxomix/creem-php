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

    /**
     * @param array{id?: string, email?: string}|null $customer
     * @param array<string, mixed>|null $metadata
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
        string $productId,
        ?string $successUrl = null,
        ?string $requestId = null,
        ?int $units = null,
        ?string $discountCode = null,
        ?array $customer = null,
        ?array $metadata = null,
        ?array $customFields = null,
    ): CheckoutResource {
        $normalizedProductId = trim($productId);

        if ($normalizedProductId === '') {
            throw new InvalidConfigurationException('productId must not be empty.');
        }

        if ($units !== null && $units <= 0) {
            throw new InvalidConfigurationException('units must be greater than 0 when provided.');
        }

        $normalizedDiscountCode = null;

        if ($discountCode !== null) {
            $normalizedDiscountCode = trim($discountCode);

            if ($normalizedDiscountCode === '') {
                throw new InvalidConfigurationException('discountCode must not be empty when provided.');
            }
        }

        $body = [
            'product_id' => $normalizedProductId,
        ];

        if ($units !== null) {
            $body['units'] = $units;
        }

        if ($normalizedDiscountCode !== null) {
            $body['discount_code'] = $normalizedDiscountCode;
        }

        $normalizedCustomer = $this->normalizeCustomerPrefill($customer);

        if ($normalizedCustomer !== null) {
            $body['customer'] = $normalizedCustomer;
        }

        if ($metadata !== null) {
            $body['metadata'] = $this->normalizeMetadata($metadata);
        }

        $normalizedCustomFields = $this->normalizeCustomFields($customFields);

        if ($normalizedCustomFields !== null) {
            $body['custom_fields'] = $normalizedCustomFields;
        }

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

    /**
     * @param array{id?: string, email?: string}|null $customer
     * @return array{id?: string, email?: string}|null
     */
    private function normalizeCustomerPrefill(?array $customer): ?array
    {
        if ($customer === null) {
            return null;
        }

        $normalizedId = null;
        $normalizedEmail = null;

        if (array_key_exists('id', $customer)) {
            if (! is_string($customer['id'])) {
                throw new InvalidConfigurationException('customer.id must be a string when provided.');
            }

            $normalizedId = trim($customer['id']);

            if ($normalizedId === '') {
                throw new InvalidConfigurationException('customer.id must not be empty when provided.');
            }
        }

        if (array_key_exists('email', $customer)) {
            if (! is_string($customer['email'])) {
                throw new InvalidConfigurationException('customer.email must be a string when provided.');
            }

            $normalizedEmail = trim($customer['email']);

            if ($normalizedEmail === '') {
                throw new InvalidConfigurationException('customer.email must not be empty when provided.');
            }

            if (filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidConfigurationException('customer.email must be a valid email address.');
            }
        }

        if ($normalizedId === null && $normalizedEmail === null) {
            throw new InvalidConfigurationException('customer must include either id or email.');
        }

        if ($normalizedId !== null && $normalizedEmail !== null) {
            throw new InvalidConfigurationException('customer must include either id or email, not both.');
        }

        return $normalizedId !== null
            ? ['id' => $normalizedId]
            : ['email' => $normalizedEmail];
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function normalizeMetadata(array $metadata): array
    {
        $normalized = [];

        foreach ($metadata as $key => $value) {
            if (! is_string($key)) {
                throw new InvalidConfigurationException('metadata keys must be strings.');
            }

            $normalizedKey = trim($key);

            if ($normalizedKey === '') {
                throw new InvalidConfigurationException('metadata keys must not be empty.');
            }

            $normalized[$normalizedKey] = $value;
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
