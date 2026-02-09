<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Pagination;

final class PaginatedResponse
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly array $payload,
        private readonly ?Pagination $pagination,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function pagination(): ?Pagination
    {
        return $this->pagination;
    }
}
