<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Webhook;

use Noxomix\CreemPhp\Exception\InvalidWebhookPayloadException;
use JsonException;

final class WebhookEventParser
{
    public function parse(string $rawPayload): WebhookEvent
    {
        if (trim($rawPayload) === '') {
            throw new InvalidWebhookPayloadException('Webhook payload must not be empty.');
        }

        try {
            $decoded = json_decode($rawPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidWebhookPayloadException('Webhook payload is not valid JSON.', 0, $exception);
        }

        if (! is_array($decoded)) {
            throw new InvalidWebhookPayloadException('Webhook payload must decode to an object.');
        }

        $eventId = $this->requiredString($decoded, 'id');
        $eventType = $this->requiredString($decoded, 'eventType');
        $object = $decoded['object'] ?? null;

        if (! is_array($object)) {
            throw new InvalidWebhookPayloadException('Webhook payload object field must be a JSON object.');
        }

        return new WebhookEvent(
            id: $eventId,
            eventType: $eventType,
            createdAt: $this->optionalInt($decoded['created_at'] ?? null),
            object: $object,
            rawPayload: $rawPayload,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredString(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidWebhookPayloadException(sprintf('Webhook payload field "%s" must be a non-empty string.', $key));
        }

        return trim($value);
    }

    private function optionalInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '' && ctype_digit($value)) {
            return (int) $value;
        }

        return null;
    }
}
