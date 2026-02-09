<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Http;

use Noxomix\CreemPhp\Exception\NetworkException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class GuzzleTransport implements HttpTransportInterface
{
    private readonly Client $client;
    private readonly LoggerInterface $logger;

    public function __construct(
        string $baseUrl,
        ?Client $client = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->client = $client ?? new Client([
            'base_uri' => rtrim($baseUrl, '/'),
        ]);
        $this->logger = $logger ?? new NullLogger();
    }

    public function send(HttpRequest $request): HttpResponse
    {
        $options = [
            'http_errors' => false,
            'headers' => $request->headers(),
            'query' => $request->query(),
            'timeout' => $request->requestTimeoutSeconds(),
            'connect_timeout' => $request->connectTimeoutSeconds(),
        ];

        if ($request->body() !== null) {
            $options['json'] = $request->body();
        }

        try {
            $response = $this->client->request(
                $request->method(),
                $request->path(),
                $options,
            );

            /** @var array<string, array<int, string>> $headers */
            $headers = $response->getHeaders();

            return new HttpResponse(
                statusCode: $response->getStatusCode(),
                headers: $headers,
                body: (string) $response->getBody(),
            );
        } catch (ConnectException $exception) {
            $networkException = NetworkException::fromThrowable($exception, [
                $this->requestApiKey($request),
            ]);

            $this->logger->error('Creem API network connection error.', [
                'message' => $networkException->getMessage(),
                'exception_class' => $exception::class,
            ]);

            throw $networkException;
        } catch (RequestException $exception) {
            $response = $exception->getResponse();

            if ($response !== null) {
                /** @var array<string, array<int, string>> $headers */
                $headers = $response->getHeaders();

                return new HttpResponse(
                    statusCode: $response->getStatusCode(),
                    headers: $headers,
                    body: (string) $response->getBody(),
                );
            }

            $networkException = NetworkException::fromThrowable($exception, [
                $this->requestApiKey($request),
            ]);

            $this->logger->error('Creem API network request exception.', [
                'message' => $networkException->getMessage(),
                'exception_class' => $exception::class,
            ]);

            throw $networkException;
        }
    }

    private function requestApiKey(HttpRequest $request): ?string
    {
        $headers = $request->headers();
        $apiKey = $headers['x-api-key'] ?? null;

        if (! is_string($apiKey)) {
            return null;
        }

        $normalized = trim($apiKey);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }
}
