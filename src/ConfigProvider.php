<?php

namespace ConductorClicktapPlatformSupport;

/**
 * Registers the `clicktap` platform: the clicktap middleware (a Mezzio application). It contributes
 * platform config only, the snapshot asset and table groups, and overrides no services, so an app on
 * this platform keeps conductor's default maintenance strategy and code-deployment state. That also
 * lets it load next to another platform package without contending for their service keys.
 */
class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'application_orchestration' => [
                'platforms' => [
                    'clicktap' => include __DIR__ . '/../config/platform.php',
                ],
            ],
        ];
    }
}
