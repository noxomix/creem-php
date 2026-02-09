<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Webhook;

final class WebhookSignatureVerifier
{
    public function verify(string $payload, ?string $providedSignature, string $secret): bool
    {
        if (trim($secret) === '') {
            return false;
        }

        $normalizedSignature = $this->normalizeSignature($providedSignature);

        if ($normalizedSignature === null) {
            return false;
        }

        $computedSignature = $this->generateSignature($payload, $secret);

        return hash_equals($computedSignature, $normalizedSignature);
    }

    public function generateSignature(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    private function normalizeSignature(?string $signature): ?string
    {
        if ($signature === null) {
            return null;
        }

        $normalized = strtolower(trim($signature));

        if ($normalized === '') {
            return null;
        }

        if (str_contains($normalized, '=')) {
            $parts = explode('=', $normalized);
            $normalized = trim((string) end($parts));
        }

        if (! preg_match('/^[a-f0-9]{64}$/', $normalized)) {
            return null;
        }

        return $normalized;
    }
}
