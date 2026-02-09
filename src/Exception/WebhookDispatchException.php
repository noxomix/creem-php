<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Exception;

use RuntimeException;
use Throwable;

final class WebhookDispatchException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?string $eventId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function eventId(): ?string
    {
        return $this->eventId;
    }

    public static function forEvent(string $eventId, Throwable $previous): self
    {
        return new self(
            message: sprintf('Failed to dispatch webhook event "%s".', $eventId),
            eventId: $eventId,
            previous: $previous,
        );
    }
}
