<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Config;

use Noxomix\CreemPhp\Exception\InvalidConfigurationException;

final class CreemConfig
{
    private const PRODUCTION_BASE_URL = 'https://api.creem.io';
    private const TEST_BASE_URL = 'https://test-api.creem.io';
    private const DEFAULT_CONNECT_TIMEOUT_SECONDS = 10.0;
    private const DEFAULT_REQUEST_TIMEOUT_SECONDS = 30.0;
    private const DEFAULT_MAX_RETRIES = 3;
    private const DEFAULT_RETRY_BASE_DELAY_MS = 1000;
    private const DEFAULT_RETRY_MAX_DELAY_MS = 4000;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $mode,
        private readonly float $connectTimeoutSeconds,
        private readonly float $requestTimeoutSeconds,
        private readonly int $maxRetries,
        private readonly int $retryBaseDelayMilliseconds,
        private readonly int $retryMaxDelayMilliseconds,
    ) {
    }

    /**
     * @param array{
     *     connect_timeout?: float|int,
     *     request_timeout?: float|int,
     *     max_retries?: int,
     *     retry_base_delay_ms?: int,
     *     retry_max_delay_ms?: int
     * } $options
     */
    public static function fromApiKey(string $apiKey, string $mode = 'prod', array $options = []): self
    {
        $normalizedMode = self::normalizeMode($mode);
        $normalizedApiKey = trim($apiKey);
        $normalizedOptions = self::normalizeOptions($options);

        if ($normalizedApiKey === '') {
            throw new InvalidConfigurationException('Creem API key must not be empty.');
        }

        return new self(
            apiKey: $normalizedApiKey,
            baseUrl: $normalizedMode === 'prod' ? self::PRODUCTION_BASE_URL : self::TEST_BASE_URL,
            mode: $normalizedMode,
            connectTimeoutSeconds: $normalizedOptions['connect_timeout'],
            requestTimeoutSeconds: $normalizedOptions['request_timeout'],
            maxRetries: $normalizedOptions['max_retries'],
            retryBaseDelayMilliseconds: $normalizedOptions['retry_base_delay_ms'],
            retryMaxDelayMilliseconds: $normalizedOptions['retry_max_delay_ms'],
        );
    }

    /**
     * @param array{
     *     connect_timeout?: float|int,
     *     request_timeout?: float|int,
     *     max_retries?: int,
     *     retry_base_delay_ms?: int,
     *     retry_max_delay_ms?: int
     * } $options
     */
    public static function fromBaseUrl(string $apiKey, string $baseUrl, string $mode = 'prod', array $options = []): self
    {
        $normalizedApiKey = trim($apiKey);
        $normalizedBaseUrl = rtrim(trim($baseUrl), '/');
        $normalizedMode = self::normalizeMode($mode);
        $normalizedOptions = self::normalizeOptions($options);

        if ($normalizedApiKey === '') {
            throw new InvalidConfigurationException('Creem API key must not be empty.');
        }

        if ($normalizedBaseUrl === '') {
            throw new InvalidConfigurationException('Creem base URL must not be empty.');
        }

        return new self(
            apiKey: $normalizedApiKey,
            baseUrl: $normalizedBaseUrl,
            mode: $normalizedMode,
            connectTimeoutSeconds: $normalizedOptions['connect_timeout'],
            requestTimeoutSeconds: $normalizedOptions['request_timeout'],
            maxRetries: $normalizedOptions['max_retries'],
            retryBaseDelayMilliseconds: $normalizedOptions['retry_base_delay_ms'],
            retryMaxDelayMilliseconds: $normalizedOptions['retry_max_delay_ms'],
        );
    }

    public function apiKey(): string
    {
        return $this->apiKey;
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function connectTimeoutSeconds(): float
    {
        return $this->connectTimeoutSeconds;
    }

    public function requestTimeoutSeconds(): float
    {
        return $this->requestTimeoutSeconds;
    }

    public function maxRetries(): int
    {
        return $this->maxRetries;
    }

    public function retryBaseDelayMilliseconds(): int
    {
        return $this->retryBaseDelayMilliseconds;
    }

    public function retryMaxDelayMilliseconds(): int
    {
        return $this->retryMaxDelayMilliseconds;
    }

    private static function normalizeMode(string $mode): string
    {
        $normalizedMode = strtolower(trim($mode));

        if ($normalizedMode === 'production') {
            return 'prod';
        }

        if (! in_array($normalizedMode, ['prod', 'test', 'sandbox'], true)) {
            throw new InvalidConfigurationException(sprintf(
                'Unsupported Creem mode "%s". Allowed values: prod, test, sandbox.',
                $mode,
            ));
        }

        return $normalizedMode;
    }

    /**
     * @param array<string, mixed> $options
     * @return array{
     *     connect_timeout: float,
     *     request_timeout: float,
     *     max_retries: int,
     *     retry_base_delay_ms: int,
     *     retry_max_delay_ms: int
     * }
     */
    private static function normalizeOptions(array $options): array
    {
        $connectTimeout = self::normalizePositiveFloat(
            $options['connect_timeout'] ?? self::DEFAULT_CONNECT_TIMEOUT_SECONDS,
            'connect_timeout',
        );
        $requestTimeout = self::normalizePositiveFloat(
            $options['request_timeout'] ?? self::DEFAULT_REQUEST_TIMEOUT_SECONDS,
            'request_timeout',
        );
        $maxRetries = self::normalizeNonNegativeInt(
            $options['max_retries'] ?? self::DEFAULT_MAX_RETRIES,
            'max_retries',
        );
        $retryBaseDelay = self::normalizePositiveInt(
            $options['retry_base_delay_ms'] ?? self::DEFAULT_RETRY_BASE_DELAY_MS,
            'retry_base_delay_ms',
        );
        $retryMaxDelay = self::normalizePositiveInt(
            $options['retry_max_delay_ms'] ?? self::DEFAULT_RETRY_MAX_DELAY_MS,
            'retry_max_delay_ms',
        );

        if ($retryMaxDelay < $retryBaseDelay) {
            throw new InvalidConfigurationException('retry_max_delay_ms must be greater than or equal to retry_base_delay_ms.');
        }

        return [
            'connect_timeout' => $connectTimeout,
            'request_timeout' => $requestTimeout,
            'max_retries' => $maxRetries,
            'retry_base_delay_ms' => $retryBaseDelay,
            'retry_max_delay_ms' => $retryMaxDelay,
        ];
    }

    private static function normalizePositiveFloat(mixed $value, string $key): float
    {
        if (! is_numeric($value)) {
            throw new InvalidConfigurationException(sprintf('%s must be a positive number.', $key));
        }

        $normalized = (float) $value;

        if ($normalized <= 0.0) {
            throw new InvalidConfigurationException(sprintf('%s must be greater than 0.', $key));
        }

        return $normalized;
    }

    private static function normalizePositiveInt(mixed $value, string $key): int
    {
        if (! is_int($value)) {
            throw new InvalidConfigurationException(sprintf('%s must be a positive integer.', $key));
        }

        if ($value <= 0) {
            throw new InvalidConfigurationException(sprintf('%s must be greater than 0.', $key));
        }

        return $value;
    }

    private static function normalizeNonNegativeInt(mixed $value, string $key): int
    {
        if (! is_int($value)) {
            throw new InvalidConfigurationException(sprintf('%s must be a non-negative integer.', $key));
        }

        if ($value < 0) {
            throw new InvalidConfigurationException(sprintf('%s must be greater than or equal to 0.', $key));
        }

        return $value;
    }
}
