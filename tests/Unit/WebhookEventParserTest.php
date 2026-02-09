<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Unit;

use Noxomix\CreemPhp\Exception\InvalidWebhookPayloadException;
use Noxomix\CreemPhp\Subscription\SubscriptionStatus;
use Noxomix\CreemPhp\Webhook\WebhookEventParser;
use Noxomix\CreemPhp\Webhook\WebhookEventType;
use PHPUnit\Framework\TestCase;

final class WebhookEventParserTest extends TestCase
{
    public function test_it_parses_checkout_completed_payload(): void
    {
        $parser = new WebhookEventParser();
        $event = $parser->parse($this->fixture('checkout.completed.json'));

        $this->assertSame('evt_checkout_1', $event->id());
        $this->assertSame(WebhookEventType::CHECKOUT_COMPLETED, $event->type());
        $this->assertSame('ch_1', $event->checkout()?->id());
        $this->assertSame('sub_1', $event->subscription()?->id());
        $this->assertSame(SubscriptionStatus::ACTIVE, $event->subscription()?->status());
    }

    public function test_it_parses_refund_and_dispute_payloads(): void
    {
        $parser = new WebhookEventParser();
        $refundEvent = $parser->parse($this->fixture('refund.created.json'));
        $disputeEvent = $parser->parse($this->fixture('dispute.created.json'));

        $this->assertSame(WebhookEventType::REFUND_CREATED, $refundEvent->type());
        $this->assertSame('ref_1', $refundEvent->refund()?->id());
        $this->assertSame('refunded', $refundEvent->refund()?->status());

        $this->assertSame(WebhookEventType::DISPUTE_CREATED, $disputeEvent->type());
        $this->assertSame('dsp_1', $disputeEvent->dispute()?->id());
        $this->assertSame('chargeback', $disputeEvent->dispute()?->status());
    }

    public function test_it_throws_for_invalid_payload(): void
    {
        $parser = new WebhookEventParser();

        $this->expectException(InvalidWebhookPayloadException::class);

        $parser->parse('{"eventType":"checkout.completed"}');
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
