<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Unit;

use Noxomix\CreemPhp\Pagination\PaginationExtractor;
use PHPUnit\Framework\TestCase;

final class PaginationExtractorTest extends TestCase
{
    public function test_it_extracts_standard_page_number_page_size_shape(): void
    {
        $pagination = PaginationExtractor::fromPayload([
            'page_number' => 2,
            'page_size' => 25,
            'total_items' => 80,
            'total_pages' => 4,
        ]);

        $this->assertNotNull($pagination);
        $this->assertSame(2, $pagination->pageNumber());
        $this->assertSame(25, $pagination->pageSize());
        $this->assertSame(80, $pagination->totalItems());
        $this->assertSame(4, $pagination->totalPages());
    }

    public function test_it_extracts_current_page_total_records_shape_and_infers_page_size_from_items(): void
    {
        $pagination = PaginationExtractor::fromPayload([
            'items' => [
                ['id' => 'prod_1'],
                ['id' => 'prod_2'],
                ['id' => 'prod_3'],
            ],
            'pagination' => [
                'current_page' => 1,
                'total_records' => 25,
                'total_pages' => 9,
            ],
        ]);

        $this->assertNotNull($pagination);
        $this->assertSame(1, $pagination->pageNumber());
        $this->assertSame(3, $pagination->pageSize());
        $this->assertSame(25, $pagination->totalItems());
        $this->assertSame(9, $pagination->totalPages());
    }
}
