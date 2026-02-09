<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Webhook;

final class WebhookProcessResult
{
    private const STATUS_PROCESSED = 'processed';
    private const STATUS_DUPLICATE = 'duplicate';
    private const STATUS_IGNORED = 'ignored';

    private function __construct(
        private readonly string $status,
        private readonly WebhookEvent $event,
    ) {
    }

    public static function processed(WebhookEvent $event): self
    {
        return new self(self::STATUS_PROCESSED, $event);
    }

    public static function duplicate(WebhookEvent $event): self
    {
        return new self(self::STATUS_DUPLICATE, $event);
    }

    public static function ignored(WebhookEvent $event): self
    {
        return new self(self::STATUS_IGNORED, $event);
    }

    public function status(): string
    {
        return $this->status;
    }

    public function event(): WebhookEvent
    {
        return $this->event;
    }

    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    public function isDuplicate(): bool
    {
        return $this->status === self::STATUS_DUPLICATE;
    }

    public function isIgnored(): bool
    {
        return $this->status === self::STATUS_IGNORED;
    }
}
