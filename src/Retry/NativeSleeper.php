<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Retry;

final class NativeSleeper implements SleeperInterface
{
    public function sleepMilliseconds(int $milliseconds): void
    {
        if ($milliseconds <= 0) {
            return;
        }

        usleep($milliseconds * 1000);
    }
}
