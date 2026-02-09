<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Exception;

use Throwable;

final class NetworkException extends ApiException
{
    /**
     * @param array<int, string|null> $secrets
     */
    public static function fromThrowable(Throwable $throwable, array $secrets = []): self
    {
        return new self(
            message: 'Network error while communicating with Creem API: '.self::sanitizeMessage(
                $throwable->getMessage(),
                $secrets,
            ),
            errorType: 'network',
            previous: $throwable,
        );
    }

    /**
     * @param array<int, string|null> $secrets
     */
    private static function sanitizeMessage(string $message, array $secrets): string
    {
        $sanitized = $message;

        foreach ($secrets as $secret) {
            if (! is_string($secret)) {
                continue;
            }

            $normalized = trim($secret);

            if ($normalized === '') {
                continue;
            }

            $sanitized = str_replace($normalized, '[REDACTED]', $sanitized);
        }

        $maskedHeader = preg_replace(
            '/(x-api-key\s*[:=]\s*)([^\s,;]+)/i',
            '$1[REDACTED]',
            $sanitized,
        );
        $sanitized = is_string($maskedHeader) ? $maskedHeader : $sanitized;

        $maskedToken = preg_replace('/creem_[A-Za-z0-9_-]+/', 'creem_[REDACTED]', $sanitized);
        $sanitized = is_string($maskedToken) ? $maskedToken : $sanitized;

        return $sanitized;
    }
}
