<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Integration;

use Noxomix\CreemPhp\CreemClient;
use PHPUnit\Framework\TestCase;

abstract class IntegrationTestCase extends TestCase
{
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

        return new CreemClient($config);
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
}
