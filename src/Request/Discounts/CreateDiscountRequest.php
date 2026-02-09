<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Request\Discounts;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Request\RequestPayloadInterface;

final class CreateDiscountRequest implements RequestPayloadInterface
{
    /**
     * @param array<int, string> $appliesToProducts
     * @param array<string, mixed> $extra
     */
    public function __construct(
        private readonly string $name,
        private readonly string $type,
        private readonly string $duration,
        private readonly array $appliesToProducts,
        private readonly ?int $percentage = null,
        private readonly ?int $amount = null,
        private readonly ?string $currency = null,
        private readonly ?string $code = null,
        private readonly ?int $durationInMonths = null,
        private readonly ?int $maxRedemptions = null,
        private readonly ?string $expiryDate = null,
        private readonly ?string $requestId = null,
        private readonly array $extra = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $normalizedName = trim($this->name);
        $normalizedType = strtolower(trim($this->type));
        $normalizedDuration = strtolower(trim($this->duration));
        $normalizedProducts = $this->normalizeProducts($this->appliesToProducts);

        if ($normalizedName === '') {
            throw new InvalidConfigurationException('name must not be empty.');
        }

        if (! in_array($normalizedType, ['percentage', 'fixed'], true)) {
            throw new InvalidConfigurationException('type must be "percentage" or "fixed".');
        }

        if (! in_array($normalizedDuration, ['forever', 'once', 'repeating'], true)) {
            throw new InvalidConfigurationException('duration must be "forever", "once", or "repeating".');
        }

        if ($normalizedProducts === []) {
            throw new InvalidConfigurationException('appliesToProducts must include at least one product ID.');
        }

        $payload = [
            'name' => $normalizedName,
            'type' => $normalizedType,
            'duration' => $normalizedDuration,
            'applies_to_products' => $normalizedProducts,
        ];

        if ($this->code !== null) {
            $normalizedCode = trim($this->code);

            if ($normalizedCode !== '') {
                $payload['code'] = $normalizedCode;
            }
        }

        if ($normalizedType === 'percentage') {
            if ($this->percentage === null || $this->percentage <= 0) {
                throw new InvalidConfigurationException('percentage must be greater than 0 for percentage discounts.');
            }

            $payload['percentage'] = $this->percentage;
        }

        if ($normalizedType === 'fixed') {
            if ($this->amount === null || $this->amount <= 0) {
                throw new InvalidConfigurationException('amount must be greater than 0 for fixed discounts.');
            }

            $normalizedCurrency = $this->currency === null ? '' : strtoupper(trim($this->currency));

            if ($normalizedCurrency === '') {
                throw new InvalidConfigurationException('currency must not be empty for fixed discounts.');
            }

            $payload['amount'] = $this->amount;
            $payload['currency'] = $normalizedCurrency;
        }

        if ($normalizedDuration === 'repeating') {
            if ($this->durationInMonths === null || $this->durationInMonths <= 0) {
                throw new InvalidConfigurationException('durationInMonths must be greater than 0 for repeating discounts.');
            }

            $payload['duration_in_months'] = $this->durationInMonths;
        }

        if ($this->maxRedemptions !== null) {
            if ($this->maxRedemptions <= 0) {
                throw new InvalidConfigurationException('maxRedemptions must be greater than 0 when provided.');
            }

            $payload['max_redemptions'] = $this->maxRedemptions;
        }

        if ($this->expiryDate !== null) {
            $normalizedExpiryDate = trim($this->expiryDate);

            if ($normalizedExpiryDate === '') {
                throw new InvalidConfigurationException('expiryDate must not be empty when provided.');
            }

            $payload['expiry_date'] = $normalizedExpiryDate;
        }

        return array_merge($this->extra, $payload);
    }

    public function requestId(): ?string
    {
        $normalized = $this->requestId === null ? null : trim($this->requestId);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
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
