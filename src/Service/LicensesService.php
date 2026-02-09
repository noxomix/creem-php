<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Service;

use Noxomix\CreemPhp\CreemClient;
use Noxomix\CreemPhp\Http\RequestOptions;
use Noxomix\CreemPhp\Request\Licenses\ActivateLicenseRequest;
use Noxomix\CreemPhp\Request\Licenses\DeactivateLicenseRequest;
use Noxomix\CreemPhp\Request\Licenses\ValidateLicenseRequest;
use Noxomix\CreemPhp\Resource\LicenseResource;

final class LicensesService
{
    public function __construct(
        private readonly CreemClient $client,
    ) {
    }

    /**
     * @param ActivateLicenseRequest $payload
     * @return LicenseResource
     */
    public function activate(ActivateLicenseRequest $payload): LicenseResource
    {
        return new LicenseResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: '/v1/licenses/activate',
            body: $payload->toArray(),
            requestId: $payload->requestId(),
        )));
    }

    /**
     * @param ValidateLicenseRequest $payload
     * @return LicenseResource
     */
    public function validate(ValidateLicenseRequest $payload): LicenseResource
    {
        return new LicenseResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: '/v1/licenses/validate',
            body: $payload->toArray(),
            requestId: $payload->requestId(),
        )));
    }

    /**
     * @param DeactivateLicenseRequest $payload
     * @return LicenseResource
     */
    public function deactivate(DeactivateLicenseRequest $payload): LicenseResource
    {
        return new LicenseResource($this->client->request(new RequestOptions(
            method: 'POST',
            path: '/v1/licenses/deactivate',
            body: $payload->toArray(),
            requestId: $payload->requestId(),
        )));
    }
}
