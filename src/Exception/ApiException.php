<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Exception;

use RuntimeException;
use Throwable;

class ApiException extends RuntimeException
{
    /**
     * @param array<int, string> $messages
     */
    public function __construct(
        string $message = 'Creem API request failed.',
        private readonly ?int $statusCode = null,
        private readonly ?string $traceId = null,
        private readonly ?string $errorType = null,
        private readonly array $messages = [],
        private readonly ?string $vendorCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }

    public function traceId(): ?string
    {
        return $this->traceId;
    }

    public function errorType(): ?string
    {
        return $this->errorType;
    }

    /**
     * @return array<int, string>
     */
    public function messages(): array
    {
        return $this->messages;
    }

    public function vendorCode(): ?string
    {
        return $this->vendorCode;
    }
}
