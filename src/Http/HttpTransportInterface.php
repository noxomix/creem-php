<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Http;

interface HttpTransportInterface
{
    public function send(HttpRequest $request): HttpResponse;
}
