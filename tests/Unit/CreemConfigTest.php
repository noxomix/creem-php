<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Unit;

use Noxomix\CreemPhp\Config\CreemConfig;
use Noxomix\CreemPhp\Config\EnvMode;
use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

final class CreemConfigTest extends TestCase
{
    public function test_it_uses_test_url_for_test_mode(): void
    {
        $config = CreemConfig::fromApiKey('creem_test_key', 'test');

        $this->assertSame('https://test-api.creem.io', $config->baseUrl());
        $this->assertSame('test', $config->mode());
    }

    public function test_it_uses_production_url_for_prod_mode(): void
    {
        $config = CreemConfig::fromApiKey('creem_live_key', 'prod');

        $this->assertSame('https://api.creem.io', $config->baseUrl());
        $this->assertSame('prod', $config->mode());
    }

    public function test_it_throws_for_invalid_mode(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        CreemConfig::fromApiKey('creem_any_key', 'invalid');
    }

    public function test_it_throws_for_empty_api_key(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        CreemConfig::fromApiKey('');
    }

    public function test_it_accepts_sandbox_mode(): void
    {
        $config = CreemConfig::fromApiKey('creem_sandbox_key', 'sandbox');

        $this->assertSame('sandbox', $config->mode());
        $this->assertSame('https://test-api.creem.io', $config->baseUrl());
    }

    public function test_it_accepts_mode_enum_input(): void
    {
        $config = CreemConfig::fromApiKey('creem_test_key', EnvMode::TEST);

        $this->assertSame(EnvMode::TEST, $config->modeEnum());
        $this->assertSame('test', $config->mode());
        $this->assertSame('https://test-api.creem.io', $config->baseUrl());
    }

    public function test_it_applies_default_timeout_and_retry_values(): void
    {
        $config = CreemConfig::fromApiKey('creem_test_key');

        $this->assertSame('test', $config->mode());
        $this->assertSame('https://test-api.creem.io', $config->baseUrl());
        $this->assertSame(10.0, $config->connectTimeoutSeconds());
        $this->assertSame(30.0, $config->requestTimeoutSeconds());
        $this->assertSame(3, $config->maxRetries());
        $this->assertSame(1000, $config->retryBaseDelayMilliseconds());
        $this->assertSame(4000, $config->retryMaxDelayMilliseconds());
    }

    public function test_it_allows_overriding_timeout_and_retry_values(): void
    {
        $config = CreemConfig::fromApiKey('creem_test_key', 'test', [
            'connect_timeout' => 2.5,
            'request_timeout' => 15,
            'max_retries' => 1,
            'retry_base_delay_ms' => 250,
            'retry_max_delay_ms' => 1200,
        ]);

        $this->assertSame(2.5, $config->connectTimeoutSeconds());
        $this->assertSame(15.0, $config->requestTimeoutSeconds());
        $this->assertSame(1, $config->maxRetries());
        $this->assertSame(250, $config->retryBaseDelayMilliseconds());
        $this->assertSame(1200, $config->retryMaxDelayMilliseconds());
    }

    public function test_it_throws_for_invalid_retry_delay_configuration(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        CreemConfig::fromApiKey('creem_test_key', 'test', [
            'retry_base_delay_ms' => 1500,
            'retry_max_delay_ms' => 1000,
        ]);
    }
}
