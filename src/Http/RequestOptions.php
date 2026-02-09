<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Http;

final class RequestOptions
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed>|null $body
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query = [],
        private readonly ?array $body = null,
        private readonly ?string $requestId = null,
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

    public function requestId(): ?string
    {
        return $this->requestId;
    }
}
