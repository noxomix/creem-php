<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Http;

final class HttpRequest
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $query
     * @param array<string, mixed>|null $body
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $headers,
        private readonly array $query,
        private readonly ?array $body,
        private readonly float $connectTimeoutSeconds,
        private readonly float $requestTimeoutSeconds,
    ) {
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return $this->query;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function body(): ?array
    {
        return $this->body;
    }

    public function connectTimeoutSeconds(): float
    {
        return $this->connectTimeoutSeconds;
    }

    public function requestTimeoutSeconds(): float
    {
        return $this->requestTimeoutSeconds;
    }
}
