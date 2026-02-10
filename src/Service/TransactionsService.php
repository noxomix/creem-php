<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Service;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Http\RequestOptions;
use Noxomix\CreemPhp\Pagination\PaginatedResponse;
use Noxomix\CreemPhp\Pagination\PaginationExtractor;
use Noxomix\CreemPhp\Resource\TransactionResource;

final class TransactionsService
{
    public function __construct(
        private readonly CreemClient $client,
    ) {
    }

    public function retrieve(string $transactionId): TransactionResource
    {
        $normalizedTransactionId = trim($transactionId);

        if ($normalizedTransactionId === '') {
            throw new InvalidConfigurationException('transactionId must not be empty.');
        }

        return new TransactionResource($this->client->request(new RequestOptions(
            method: 'GET',
            path: '/v1/transactions',
            query: ['transaction_id' => $normalizedTransactionId],
        )));
    }

    /**
     * @param array<string, mixed> $query
     */
    public function search(array $query = []): PaginatedResponse
    {
        $payload = $this->client->request(new RequestOptions(
            method: 'GET',
            path: '/v1/transactions/search',
            query: $query,
        ));

        return new PaginatedResponse(
            payload: $payload,
            pagination: PaginationExtractor::fromPayload($payload),
        );
    }
}
