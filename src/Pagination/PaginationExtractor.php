<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Pagination;

final class PaginationExtractor
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): ?Pagination
    {
        $candidates = [$payload];

        if (isset($payload['pagination']) && is_array($payload['pagination'])) {
            /** @var array<string, mixed> $pagination */
            $pagination = $payload['pagination'];
            $candidates[] = $pagination;
        }

        if (
            isset($payload['meta']) &&
            is_array($payload['meta']) &&
            isset($payload['meta']['pagination']) &&
            is_array($payload['meta']['pagination'])
        ) {
            /** @var array<string, mixed> $metaPagination */
            $metaPagination = $payload['meta']['pagination'];
            $candidates[] = $metaPagination;
        }

        foreach ($candidates as $candidate) {
            $pageNumber = self::normalizeIntOrNull(
                $candidate['page_number'] ?? $candidate['current_page'] ?? null,
            );
            $pageSize = self::normalizeIntOrNull(
                $candidate['page_size'] ?? $candidate['limit'] ?? $candidate['per_page'] ?? null,
            );

            if ($pageSize === null && isset($payload['items']) && is_array($payload['items'])) {
                $pageSize = count($payload['items']);
            }

            if ($pageNumber === null || $pageSize === null) {
                continue;
            }

            return new Pagination(
                pageNumber: $pageNumber,
                pageSize: $pageSize,
                totalItems: self::normalizeIntOrNull($candidate['total_items'] ?? $candidate['total_records'] ?? null),
                totalPages: self::normalizeIntOrNull($candidate['total_pages'] ?? null),
            );
        }

        return null;
    }

    private static function normalizeIntOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '' && ctype_digit($value)) {
            return (int) $value;
        }

        return null;
    }
}
