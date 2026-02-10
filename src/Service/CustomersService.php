<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Service;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Http\RequestOptions;
use Noxomix\CreemPhp\Pagination\PaginatedResponse;
use Noxomix\CreemPhp\Pagination\PaginationExtractor;
use Noxomix\CreemPhp\Resource\BillingLinkResource;
use Noxomix\CreemPhp\Resource\CustomerResource;

final class CustomersService
{
    public function __construct(
        private readonly CreemClient $client,
    ) {
    }

    public function retrieve(string $customerId): CustomerResource
    {
        $normalizedCustomerId = trim($customerId);

        if ($normalizedCustomerId === '') {
            throw new InvalidConfigurationException('customerId must not be empty.');
        }

        return new CustomerResource($this->client->request(new RequestOptions(
            method: 'GET',
            path: '/v1/customers',
            query: ['customer_id' => $normalizedCustomerId],
        )));
    }

    public function retrieveByEmail(string $email): CustomerResource
    {
        $normalizedEmail = trim($email);

        if ($normalizedEmail === '') {
            throw new InvalidConfigurationException('email must not be empty.');
        }

        return new CustomerResource($this->client->request(new RequestOptions(
            method: 'GET',
            path: '/v1/customers',
            query: ['email' => $normalizedEmail],
        )));
    }

    public function list(int $pageNumber = 1, int $pageSize = 50): PaginatedResponse
    {
        if ($pageNumber <= 0) {
            throw new InvalidConfigurationException('pageNumber must be greater than 0.');
        }

        if ($pageSize <= 0) {
            throw new InvalidConfigurationException('pageSize must be greater than 0.');
        }

        $payload = $this->client->request(new RequestOptions(
            method: 'GET',
            path: '/v1/customers/list',
            query: [
                'page_number' => $pageNumber,
                'page_size' => $pageSize,
            ],
        ));

        return new PaginatedResponse(
            payload: $payload,
            pagination: PaginationExtractor::fromPayload($payload),
        );
    }

    public function createBillingLink(string $customerId, ?string $requestId = null): BillingLinkResource
    {
        $normalizedCustomerId = trim($customerId);

        if ($normalizedCustomerId === '') {
            throw new InvalidConfigurationException('customerId must not be empty.');
        }

        return new BillingLinkResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: '/v1/customers/billing',
            body: [
                'customer_id' => $normalizedCustomerId,
            ],
            requestId: $requestId,
        )));
    }
}
