<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Request\Products;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Request\RequestPayloadInterface;

final class CreateProductRequest implements RequestPayloadInterface
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        private readonly string $name,
        private readonly int $price,
        private readonly string $currency,
        private readonly string $billingType,
        private readonly ?string $billingPeriod = null,
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
        $normalizedCurrency = strtoupper(trim($this->currency));
        $normalizedBillingType = strtolower(trim($this->billingType));

        if ($normalizedName === '') {
            throw new InvalidConfigurationException('name must not be empty.');
        }

        if ($this->price < 100) {
            throw new InvalidConfigurationException('price must be at least 100 cents.');
        }

        if ($normalizedCurrency === '') {
            throw new InvalidConfigurationException('currency must not be empty.');
        }

        if (! in_array($normalizedBillingType, ['recurring', 'onetime'], true)) {
            throw new InvalidConfigurationException('billingType must be "recurring" or "onetime".');
        }

        $payload = array_merge(
            $this->extra,
            [
                'name' => $normalizedName,
                'price' => $this->price,
                'currency' => $normalizedCurrency,
                'billing_type' => $normalizedBillingType,
            ],
        );

        if ($normalizedBillingType === 'recurring') {
            $normalizedBillingPeriod = $this->billingPeriod === null ? '' : trim($this->billingPeriod);

            if ($normalizedBillingPeriod === '') {
                throw new InvalidConfigurationException('billingPeriod must not be empty for recurring products.');
            }

            $payload['billing_period'] = $normalizedBillingPeriod;
        }

        if ($normalizedBillingType === 'onetime' && $this->billingPeriod !== null) {
            $normalizedBillingPeriod = trim($this->billingPeriod);

            if ($normalizedBillingPeriod !== '') {
                $payload['billing_period'] = $normalizedBillingPeriod;
            }
        }

        return $payload;
    }

    public function requestId(): ?string
    {
        $normalized = $this->requestId === null ? null : trim($this->requestId);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }
}
