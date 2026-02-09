<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Unit;

use Noxomix\CreemPhp\Webhook\WebhookSignatureVerifier;
use PHPUnit\Framework\TestCase;

final class WebhookSignatureVerifierTest extends TestCase
{
    private const SECRET = 'whsec_test_secret';

    public function test_it_verifies_valid_signature(): void
    {
        $payload = $this->fixture('checkout.completed.json');
        $verifier = new WebhookSignatureVerifier();
        $signature = $verifier->generateSignature($payload, self::SECRET);

        $this->assertTrue($verifier->verify($payload, $signature, self::SECRET));
    }

    public function test_it_rejects_invalid_signature(): void
    {
        $payload = $this->fixture('checkout.completed.json');
        $verifier = new WebhookSignatureVerifier();

        $this->assertFalse($verifier->verify($payload, 'invalid_signature', self::SECRET));
    }

    public function test_it_accepts_prefixed_signature_format(): void
    {
        $payload = $this->fixture('checkout.completed.json');
        $verifier = new WebhookSignatureVerifier();
        $signature = $verifier->generateSignature($payload, self::SECRET);

        $this->assertTrue($verifier->verify($payload, 'sha256='.$signature, self::SECRET));
    }

    private function fixture(string $file): string
    {
        $path = __DIR__.'/../Fixtures/webhooks/'.$file;
        $contents = file_get_contents($path);

        if ($contents === false) {
            $this->fail(sprintf('Failed to load fixture "%s".', $path));
        }

        return $contents;
    }
}
