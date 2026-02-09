<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Pagination;

final class Pagination
{
    public function __construct(
        private readonly int $pageNumber,
        private readonly int $pageSize,
        private readonly ?int $totalItems = null,
        private readonly ?int $totalPages = null,
    ) {
    }

    public function pageNumber(): int
    {
        return $this->pageNumber;
    }

    public function pageSize(): int
    {
        return $this->pageSize;
    }

    public function totalItems(): ?int
    {
        return $this->totalItems;
    }

    public function totalPages(): ?int
    {
        return $this->totalPages;
    }
}
