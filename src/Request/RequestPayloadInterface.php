<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Request;

interface RequestPayloadInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

    public function requestId(): ?string;
}
