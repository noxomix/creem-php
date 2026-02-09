<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Webhook\Dispatch;

use Noxomix\CreemPhp\Webhook\WebhookEvent;

interface WebhookEventDispatcherInterface
{
    /**
     * Returns true when the event was handled by a registered handler.
     */
    public function dispatch(WebhookEvent $event): bool;
}
