<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Http;

final class HttpResponse
{
    /**
     * @param array<string, array<int, string>> $headers
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly array $headers,
        private readonly string $body,
    ) {
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * @return array<string, mixed>
     */
    public function json(): array
    {
        $trimmedBody = trim($this->body);

        if ($trimmedBody === '') {
            return [];
        }

        $decoded = json_decode($trimmedBody, true);

        if (! is_array($decoded)) {
            return [];
        }

        return $decoded;
    }
}
