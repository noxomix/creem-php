<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Service;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Exception\InvalidConfigurationException;
use Noxomix\CreemPhp\Http\RequestOptions;
use Noxomix\CreemPhp\Resource\LicenseResource;

final class LicensesService
{
    public function __construct(
        private readonly CreemClient $client,
    ) {
    }

    public function activate(string $key, string $instanceName, ?string $requestId = null): LicenseResource
    {
        $normalizedKey = trim($key);
        $normalizedInstanceName = trim($instanceName);

        if ($normalizedKey === '') {
            throw new InvalidConfigurationException('key must not be empty.');
        }

        if ($normalizedInstanceName === '') {
            throw new InvalidConfigurationException('instanceName must not be empty.');
        }

        return new LicenseResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: '/v1/licenses/activate',
            body: [
                'key' => $normalizedKey,
                'instance_name' => $normalizedInstanceName,
            ],
            requestId: $requestId,
        )));
    }

    public function validate(string $key, string $instanceId, ?string $requestId = null): LicenseResource
    {
        $payload = $this->normalizeKeyAndInstanceId($key, $instanceId);

        return new LicenseResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: '/v1/licenses/validate',
            body: $payload,
            requestId: $requestId,
        )));
    }

    public function deactivate(string $key, string $instanceId, ?string $requestId = null): LicenseResource
    {
        $payload = $this->normalizeKeyAndInstanceId($key, $instanceId);

        return new LicenseResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: '/v1/licenses/deactivate',
            body: $payload,
            requestId: $requestId,
        )));
    }

    /**
     * @return array{key:string,instance_id:string}
     */
    private function normalizeKeyAndInstanceId(string $key, string $instanceId): array
    {
        $normalizedKey = trim($key);
        $normalizedInstanceId = trim($instanceId);

        if ($normalizedKey === '') {
            throw new InvalidConfigurationException('key must not be empty.');
        }

        if ($normalizedInstanceId === '') {
            throw new InvalidConfigurationException('instanceId must not be empty.');
        }

        return [
            'key' => $normalizedKey,
            'instance_id' => $normalizedInstanceId,
        ];
    }
}
