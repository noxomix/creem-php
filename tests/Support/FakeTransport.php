<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Support;

use Noxomix\CreemPhp\Http\HttpRequest;
use Noxomix\CreemPhp\Http\HttpResponse;
use Noxomix\CreemPhp\Http\HttpTransportInterface;
use RuntimeException;
use Throwable;

final class FakeTransport implements HttpTransportInterface
{
    /** @var array<int, HttpResponse|Throwable> */
    private array $queue = [];

    /** @var array<int, HttpRequest> */
    private array $requests = [];

    /**
     * @param array<int, HttpResponse|Throwable> $queue
     */
    public function __construct(array $queue = [])
    {
        $this->queue = array_values($queue);
    }

    public function send(HttpRequest $request): HttpResponse
    {
        $this->requests[] = $request;

        if ($this->queue === []) {
            throw new RuntimeException('FakeTransport queue is empty.');
        }

        $next = array_shift($this->queue);

        if ($next instanceof Throwable) {
            throw $next;
        }

        return $next;
    }

    /**
     * @return array<int, HttpRequest>
     */
    public function requests(): array
    {
        return $this->requests;
    }
}
