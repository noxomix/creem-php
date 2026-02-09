<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Webhook\Dispatch;

use Noxomix\CreemPhp\Webhook\WebhookEvent;

final class DefaultWebhookDispatcher implements WebhookEventDispatcherInterface
{
    /** @var array<string, callable(WebhookEvent): void> */
    private array $handlers;

    /** @var callable(WebhookEvent): void|null */
    private $unknownEventHandler;

    /**
     * @param array<string, callable(WebhookEvent): void> $handlers
     * @param callable(WebhookEvent): void|null $unknownEventHandler
     */
    public function __construct(
        array $handlers = [],
        ?callable $unknownEventHandler = null,
    ) {
        $this->handlers = [];

        foreach ($handlers as $eventType => $handler) {
            $this->handlers[strtolower(trim($eventType))] = $handler;
        }

        $this->unknownEventHandler = $unknownEventHandler;
    }

    public function dispatch(WebhookEvent $event): bool
    {
        $eventType = strtolower(trim($event->eventType()));

        if (isset($this->handlers[$eventType])) {
            $handler = $this->handlers[$eventType];
            $handler($event);

            return true;
        }

        if ($this->unknownEventHandler !== null) {
            $handler = $this->unknownEventHandler;
            $handler($event);

            return true;
        }

        return false;
    }
}
