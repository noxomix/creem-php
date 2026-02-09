<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Retry;

interface SleeperInterface
{
    public function sleepMilliseconds(int $milliseconds): void;
}
