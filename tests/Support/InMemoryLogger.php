<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Support;

use Psr\Log\AbstractLogger;
use Stringable;

final class InMemoryLogger extends AbstractLogger
{
    /** @var array<int, array{level:string,message:string,context:array<string, mixed>}> */
    private array $records = [];

    /**
     * @param array<string, mixed> $context
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->records[] = [
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    /**
     * @return array<int, array{level:string,message:string,context:array<string, mixed>}>
     */
    public function records(): array
    {
        return $this->records;
    }

    public function contains(string $needle): bool
    {
        foreach ($this->records as $record) {
            $encoded = json_encode($record);

            if (is_string($encoded) && str_contains($encoded, $needle)) {
                return true;
            }
        }

        return false;
    }
}
