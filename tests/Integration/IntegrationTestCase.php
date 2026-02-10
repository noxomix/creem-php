<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Integration;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Exception\NotFoundException;
use Noxomix\CreemPhp\Product\BillingType;
use PHPUnit\Framework\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    private ?CreemClient $integrationClient = null;
    /** @var list<string> */
    private array $discountIdsForCleanup = [];

    protected function createIntegrationClient(): CreemClient
    {
        $this->requireIntegrationEnabled();

        $apiKey = $this->requiredEnv('CREEM_API_KEY');
        $mode = $this->optionalEnv('CREEM_MODE') ?? 'test';
        $baseUrl = $this->optionalEnv('CREEM_BASE_URL');

        $config = [
            'api_key' => $apiKey,
            'mode' => $mode,
        ];

        if ($baseUrl !== null) {
            $config['base_url'] = $baseUrl;
        }

        $this->integrationClient = new CreemClient($config);

        return $this->integrationClient;
    }

    protected function requireIntegrationEnabled(): void
    {
        if ($this->optionalEnv('CREEM_RUN_INTEGRATION') !== '1') {
            $this->markTestSkipped('Live integration tests are disabled. Set CREEM_RUN_INTEGRATION=1 to enable.');
        }
    }

    protected function requiredEnv(string $name): string
    {
        $value = $this->optionalEnv($name);

        if ($value === null) {
            $this->markTestSkipped(sprintf('Missing required integration env var: %s', $name));
        }

        return $value;
    }

    protected function optionalEnv(string $name): ?string
    {
        $raw = getenv($name);

        if (! is_string($raw)) {
            return null;
        }

        $value = trim($raw);

        if ($value === '') {
            return null;
        }

        return $value;
    }

    protected function integrationProductId(CreemClient $client): string
    {
        $product = $client->products()->create(
            name: sprintf('SDK Integration %s', bin2hex(random_bytes(4))),
            description: 'Integration test product',
            price: 1900,
            currency: 'USD',
            billingType: BillingType::ONETIME,
        );

        $productId = $product->id();

        if (! is_string($productId) || trim($productId) === '') {
            $this->fail('Failed to create an integration product ID.');
        }

        return $productId;
    }

    protected function registerDiscountForCleanup(string $discountId): void
    {
        $normalized = trim($discountId);

        if ($normalized !== '') {
            $this->discountIdsForCleanup[] = $normalized;
        }
    }

    protected function tearDown(): void
    {
        if ($this->integrationClient !== null) {
            foreach ($this->discountIdsForCleanup as $discountId) {
                try {
                    $this->integrationClient->discounts()->delete($discountId);
                } catch (NotFoundException) {
                    // already deleted during test flow
                } catch (\Throwable) {
                    // cleanup must never hide test assertions
                }
            }
        }

        $this->discountIdsForCleanup = [];
        $this->integrationClient = null;

        parent::tearDown();
    }
}
