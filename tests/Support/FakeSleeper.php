<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Support;

use Noxomix\CreemPhp\Retry\SleeperInterface;

final class FakeSleeper implements SleeperInterface
{
    /** @var array<int, int> */
    private array $sleptMilliseconds = [];

    public function sleepMilliseconds(int $milliseconds): void
    {
        $this->sleptMilliseconds[] = $milliseconds;
    }

    /**
     * @return array<int, int>
     */
    public function sleptMilliseconds(): array
    {
        return $this->sleptMilliseconds;
    }
}
