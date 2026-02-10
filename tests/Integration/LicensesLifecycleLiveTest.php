<?php

declare(strict_types=1);

namespace Noxomix\CreemPhp\Tests\Integration;

final class LicensesLifecycleLiveTest extends IntegrationTestCase
{
    public function test_licenses_activate_validate_and_deactivate_cycle(): void
    {
        $client = $this->createIntegrationClient();
        $licenseKey = $this->optionalEnv('CREEM_INTEGRATION_LICENSE_KEY');

        if ($licenseKey === null) {
            $this->markTestSkipped('Missing CREEM_INTEGRATION_LICENSE_KEY for licenses integration test.');
        }

        $instanceName = sprintf('integration-%s', bin2hex(random_bytes(4)));

        $activated = $client->licenses()->activate(
            key: $licenseKey,
            instanceName: $instanceName,
        );

        $instanceId = $activated->instanceId();
        $this->assertNotNull($instanceId);

        $validated = $client->licenses()->validate(
            key: $licenseKey,
            instanceId: $instanceId,
        );
        $this->assertSame($instanceId, $validated->instanceId());

        $deactivated = $client->licenses()->deactivate(
            key: $licenseKey,
            instanceId: $instanceId,
        );
        $this->assertSame($instanceId, $deactivated->instanceId());
    }
}
