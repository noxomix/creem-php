<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp;

use Noxomix\CreemPhp\Config\CreemConfig;
use Noxomix\CreemPhp\Config\EnvMode;
use Noxomix\CreemPhp\Exception\ApiException;
use Noxomix\CreemPhp\Exception\AuthenticationException;
use Noxomix\CreemPhp\Exception\ConflictException;
use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Exception\NetworkException;
use Noxomix\CreemPhp\Exception\NotFoundException;
use Noxomix\CreemPhp\Exception\RateLimitException;
use Noxomix\CreemPhp\Exception\ServerException;
use Noxomix\CreemPhp\Exception\ValidationException;
use Noxomix\CreemPhp\Http\GuzzleTransport;
use Noxomix\CreemPhp\Http\HttpRequest;
use Noxomix\CreemPhp\Http\HttpResponse;
use Noxomix\CreemPhp\Http\HttpTransportInterface;
use Noxomix\CreemPhp\Http\RequestOptions;
use Noxomix\CreemPhp\Retry\NativeSleeper;
use Noxomix\CreemPhp\Retry\SleeperInterface;
use Noxomix\CreemPhp\Service\CheckoutsService;
use Noxomix\CreemPhp\Service\CustomersService;
use Noxomix\CreemPhp\Service\DiscountsService;
use Noxomix\CreemPhp\Service\LicensesService;
use Noxomix\CreemPhp\Service\ProductsService;
use Noxomix\CreemPhp\Service\SubscriptionsService;
use Noxomix\CreemPhp\Service\TransactionsService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class CreemClient
{
    /** @var array<int, int> */
    private const RETRYABLE_STATUS_CODES = [429, 500, 502, 503, 504];
    private readonly CreemConfig $config;
    private readonly HttpTransportInterface $transport;
    private readonly LoggerInterface $logger;
    private readonly SleeperInterface $sleeper;
    private ?CheckoutsService $checkoutsService = null;
    private ?SubscriptionsService $subscriptionsService = null;
    private ?CustomersService $customersService = null;
    private ?TransactionsService $transactionsService = null;
    private ?ProductsService $productsService = null;
    private ?DiscountsService $discountsService = null;
    private ?LicensesService $licensesService = null;

    /**
     * @param array{
     *     api_key: string,
     *     mode?: EnvMode|string,
     *     base_url?: string,
     *     connect_timeout?: float|int,
     *     request_timeout?: float|int,
     *     max_retries?: int,
     *     retry_base_delay_ms?: int,
     *     retry_max_delay_ms?: int
     * } $config
     */
    public function __construct(
        array $config,
        ?HttpTransportInterface $transport = null,
        ?LoggerInterface $logger = null,
        ?SleeperInterface $sleeper = null,
    ) {
        $this->config = self::resolveConfig($config);
        $this->logger = $logger ?? new NullLogger();
        $this->transport = $transport ?? new GuzzleTransport(
            baseUrl: $this->config->baseUrl(),
            logger: $this->logger,
        );
        $this->sleeper = $sleeper ?? new NativeSleeper();
    }

    public function apiKey(): string
    {
        return $this->config->apiKey();
    }

    public function baseUrl(): string
    {
        return $this->config->baseUrl();
    }

    public function mode(): string
    {
        return $this->config->mode();
    }

    public function endpoint(string $path): string
    {
        return $this->baseUrl().$this->normalizePath($path);
    }

    public function checkouts(): CheckoutsService
    {
        return $this->checkoutsService ??= new CheckoutsService($this);
    }

    public function subscriptions(): SubscriptionsService
    {
        return $this->subscriptionsService ??= new SubscriptionsService($this);
    }

    public function customers(): CustomersService
    {
        return $this->customersService ??= new CustomersService($this);
    }

    public function transactions(): TransactionsService
    {
        return $this->transactionsService ??= new TransactionsService($this);
    }

    public function products(): ProductsService
    {
        return $this->productsService ??= new ProductsService($this);
    }

    public function discounts(): DiscountsService
    {
        return $this->discountsService ??= new DiscountsService($this);
    }

    public function licenses(): LicensesService
    {
        return $this->licensesService ??= new LicensesService($this);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed>|null $body
     * @return array<string, mixed>
     */
    public function rawRequest(
        string $method,
        string $path,
        array $query = [],
        ?array $body = null,
        ?string $requestId = null,
    ): array {
        return $this->request(new RequestOptions(
            method: $method,
            path: $path,
            query: $query,
            body: $body,
            requestId: $requestId,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function request(RequestOptions $options): array
    {
        $normalizedMethod = strtoupper(trim($options->method()));

        if ($normalizedMethod === '') {
            throw new InvalidConfigurationException('HTTP method must not be empty.');
        }

        $request = new HttpRequest(
            method: $normalizedMethod,
            path: $this->normalizePath($options->path()),
            headers: $this->defaultHeaders(),
            query: $options->query(),
            body: $this->withRequestId($normalizedMethod, $options->body(), $options->requestId()),
            connectTimeoutSeconds: $this->config->connectTimeoutSeconds(),
            requestTimeoutSeconds: $this->config->requestTimeoutSeconds(),
        );

        return $this->sendWithRetry($request);
    }

    /**
     * @return array<string, mixed>
     */
    private function sendWithRetry(HttpRequest $request): array
    {
        $attempt = 0;
        $maxRetries = $this->config->maxRetries();

        while (true) {
            try {
                $response = $this->transport->send($request);
            } catch (NetworkException $exception) {
                if (! $this->canRetry($attempt, $maxRetries)) {
                    throw $exception;
                }

                $this->sleepForRetry($attempt, 'network');
                $attempt++;

                continue;
            }

            if ($response->statusCode() < 400) {
                return $response->json();
            }

            $exception = $this->mapErrorResponse($response);

            if ($this->isRetryableStatus($response->statusCode()) && $this->canRetry($attempt, $maxRetries)) {
                $this->sleepForRetry($attempt, (string) $response->statusCode());
                $attempt++;

                continue;
            }

            throw $exception;
        }
    }

    /**
     * @return array<string, string>
     */
    private function defaultHeaders(): array
    {
        return [
            'x-api-key' => $this->apiKey(),
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ];
    }

    private function normalizePath(string $path): string
    {
        return '/'.ltrim(trim($path), '/');
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array<string, mixed>|null
     */
    private function withRequestId(string $method, ?array $body, ?string $requestId): ?array
    {
        $trimmedRequestId = $requestId === null ? null : trim($requestId);

        if ($trimmedRequestId === null || $trimmedRequestId === '') {
            return $body;
        }

        if (! in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            return $body;
        }

        $payload = $body ?? [];

        if (! array_key_exists('request_id', $payload)) {
            $payload['request_id'] = $trimmedRequestId;
        }

        return $payload;
    }

    private function canRetry(int $attempt, int $maxRetries): bool
    {
        return $attempt < $maxRetries;
    }

    private function isRetryableStatus(int $statusCode): bool
    {
        return in_array($statusCode, self::RETRYABLE_STATUS_CODES, true);
    }

    private function sleepForRetry(int $attempt, string $reason): void
    {
        $baseDelay = $this->config->retryBaseDelayMilliseconds();
        $maxDelay = $this->config->retryMaxDelayMilliseconds();
        $delay = (int) min($baseDelay * (2 ** $attempt), $maxDelay);
        $jitterUpperBound = max(1, min(250, (int) floor($delay * 0.25)));
        $jitter = random_int(0, $jitterUpperBound);
        $delayWithJitter = $delay + $jitter;

        $this->logger->warning('Retrying Creem API request.', [
            'attempt' => $attempt + 1,
            'reason' => $reason,
            'sleep_ms' => $delayWithJitter,
        ]);

        $this->sleeper->sleepMilliseconds($delayWithJitter);
    }

    private function mapErrorResponse(HttpResponse $response): ApiException
    {
        $payload = $response->json();
        $status = $response->statusCode();

        if (isset($payload['status']) && is_int($payload['status'])) {
            $status = $payload['status'];
        }

        $errorType = isset($payload['error']) && is_string($payload['error'])
            ? $payload['error']
            : null;

        $traceId = isset($payload['trace_id']) && is_string($payload['trace_id'])
            ? $payload['trace_id']
            : null;

        $vendorCode = null;

        if (isset($payload['code']) && is_string($payload['code'])) {
            $vendorCode = $payload['code'];
        }

        if (isset($payload['code']) && is_int($payload['code'])) {
            $vendorCode = (string) $payload['code'];
        }

        $messages = $this->normalizeErrorMessages($payload['message'] ?? null);
        $message = $messages[0] ?? $errorType ?? sprintf('Creem API request failed with status %d.', $status);

        return match (true) {
            $status === 401 || $status === 403 => new AuthenticationException($message, $status, $traceId, $errorType, $messages, $vendorCode),
            $status === 400 || $status === 422 => new ValidationException($message, $status, $traceId, $errorType, $messages, $vendorCode),
            $status === 404 => new NotFoundException($message, $status, $traceId, $errorType, $messages, $vendorCode),
            $status === 409 => new ConflictException($message, $status, $traceId, $errorType, $messages, $vendorCode),
            $status === 429 => new RateLimitException($message, $status, $traceId, $errorType, $messages, $vendorCode),
            $status >= 500 => new ServerException($message, $status, $traceId, $errorType, $messages, $vendorCode),
            default => new ApiException($message, $status, $traceId, $errorType, $messages, $vendorCode),
        };
    }

    /**
     * @return array<int, string>
     */
    private function normalizeErrorMessages(mixed $messages): array
    {
        if (is_string($messages) && trim($messages) !== '') {
            return [trim($messages)];
        }

        if (! is_array($messages)) {
            return [];
        }

        $normalized = [];

        foreach ($messages as $message) {
            if (is_string($message) && trim($message) !== '') {
                $normalized[] = trim($message);
            }
        }

        return $normalized;
    }

    /**
     * @param array{
     *     api_key: string,
     *     mode?: EnvMode|string,
     *     base_url?: string,
     *     connect_timeout?: float|int,
     *     request_timeout?: float|int,
     *     max_retries?: int,
     *     retry_base_delay_ms?: int,
     *     retry_max_delay_ms?: int
     * } $config
     */
    private static function resolveConfig(array $config): CreemConfig
    {
        $apiKey = $config['api_key'] ?? null;

        if (! is_string($apiKey)) {
            throw new InvalidConfigurationException('config["api_key"] must be a string.');
        }

        $mode = $config['mode'] ?? 'test';
        $baseUrl = $config['base_url'] ?? null;
        $options = [];

        foreach ([
            'connect_timeout',
            'request_timeout',
            'max_retries',
            'retry_base_delay_ms',
            'retry_max_delay_ms',
        ] as $optionKey) {
            if (array_key_exists($optionKey, $config)) {
                $options[$optionKey] = $config[$optionKey];
            }
        }

        if ($baseUrl === null) {
            return CreemConfig::fromApiKey($apiKey, $mode, $options);
        }

        if (! is_string($baseUrl)) {
            throw new InvalidConfigurationException('config["base_url"] must be a string when provided.');
        }

        return CreemConfig::fromBaseUrl($apiKey, $baseUrl, $mode, $options);
    }
}
