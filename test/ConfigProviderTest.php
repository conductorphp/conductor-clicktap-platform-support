<?php

namespace ConductorClicktapPlatformSupportTest;

use ConductorAppOrchestration\Config\SnapshotConfig;
use ConductorClicktapPlatformSupport\ConfigProvider;
use PHPUnit\Framework\TestCase;

/**
 * CTAP-2152. The `clicktap` platform contributes the middleware's snapshot groups and nothing else.
 * Groups are expanded through the real SnapshotConfig, as a plan's excludes are.
 */
class ConfigProviderTest extends TestCase
{
    private array $config;
    private SnapshotConfig $snapshotConfig;

    public function setUp(): void
    {
        $this->config = (new ConfigProvider())();
        $this->snapshotConfig = new SnapshotConfig(
            $this->config['application_orchestration']['platforms']['clicktap']['snapshot']
        );
    }

    /**
     * No service overrides: an app on this platform keeps conductor's default maintenance strategy
     * and code-deployment state, as it had on `custom`, and the package can load next to another
     * platform package without contending for their service keys.
     */
    public function testContributesPlatformConfigOnly(): void
    {
        $this->assertSame(['application_orchestration'], array_keys($this->config));
        $this->assertSame(['snapshot'], array_keys($this->config['application_orchestration']['platforms']['clicktap']));
    }

    public function testAssetCoreLeavesOutTheGeneratedSitemaps(): void
    {
        $this->assertSame(['/sitemap-*.xml', '/sitemap.xml'], $this->snapshotConfig->expandAssetGroups(['@core']));
        $this->assertSame([], $this->snapshotConfig->expandAssetGroups(['@customer_uploads']));
    }

    /** The scrubbed snapshot's table list, as CTAP-2149 set it in the clicktap middleware config. */
    public function testTableCoreIsTheScrubList(): void
    {
        $expected = [
            'address_book*',
            'admin_role_assignment',
            'admin_user*',
            'authentication_*_access_token',
            'authentication_*_refresh_token',
            'authorization_*_permission_event',
            'checkout_success',
            'company',
            'company_unit',
            'company_user',
            'historical_quote*',
            'integration_run*',
            'maintenance_mode_whitelist',
            'newsletter_subscription',
            'order*',
            'otp',
            'payment_method_*_saved_card',
            'payment_method_*_saved_card_billing_address',
            'payment_method_*_user',
            'product_alert_stock',
            'quote*',
            'scheduler_job_run',
            'user',
            'user_website',
        ];
        sort($expected);

        $this->assertSame($expected, $this->snapshotConfig->expandDatabaseTableGroups(['@core']));
    }

    /** The 1.0 purpose groups, deprecated by CTAP-2161, do not overlap, and make up @core. */
    public function testThePurposeGroupsPartitionCore(): void
    {
        $all = ['maintenance_mode_whitelist'];
        foreach (['customers', 'sales', 'payment', 'admin', 'auth', 'logs'] as $group) {
            $tables = $this->snapshotConfig->expandDatabaseTableGroups(['@' . $group]);
            $this->assertNotEmpty($tables, "Group \"$group\" is empty.");
            $this->assertSame([], array_values(array_intersect($all, $tables)), "Group \"$group\" repeats a pattern.");
            $all = [...$all, ...$tables];
        }

        $this->assertSame(count($all), count($this->snapshotConfig->expandDatabaseTableGroups(['@core'])));
    }

    /** The patterns as the mydumper export applies them: fnmatch against real middleware table names. */
    public function testPatternsMatchTheTablesTheyAreFor(): void
    {
        $core = $this->snapshotConfig->expandDatabaseTableGroups(['@core']);
        $excluded = static fn(string $table): bool => (bool) array_filter($core, static fn($p) => fnmatch($p, $table));

        foreach ([
            'authentication_frontend_access_token',
            'authentication_integration_refresh_token',
            'payment_method_square_saved_card',
            'payment_method_cybersource_saved_card_billing_address',
            'payment_method_authorizenet_user',
            'authorization_admin_permission_event',
            'order_item',
            'quote_shipment',
        ] as $table) {
            $this->assertTrue($excluded($table), "$table should be excluded.");
        }

        foreach ([
            'payment_method',
            'payment_method_attribute',
            'authorization_admin_permission_snapshot',
            'product',
            'integration_synced_entity',
            'user_attribute',
        ] as $table) {
            $this->assertFalse($excluded($table), "$table should be kept.");
        }
    }

    /** CTAP-2161: moving a plan from @core to the nature groups leaves out at least what @core did. */
    public function testTheNatureGroupsCoverCore(): void
    {
        $natures = ['@generated', '@cache', '@scratch', '@personal_data', '@environment'];

        $this->assertSame([], array_values(array_diff(
            $this->snapshotConfig->expandDatabaseTableGroups(['@core']),
            $this->snapshotConfig->expandDatabaseTableGroups($natures)
        )));
        $this->assertSame([], array_values(array_diff(
            $this->snapshotConfig->expandAssetGroups(['@core']),
            $this->snapshotConfig->expandAssetGroups($natures)
        )));
    }

    public function testNatureGroupsHoldWhatTheirNameSays(): void
    {
        $tables = fn(string $group): array => $this->snapshotConfig->expandDatabaseTableGroups(["@$group"]);

        $this->assertContains('rate_limit_counter', $tables('scratch'));
        $this->assertContains('scheduler_job_run', $tables('scratch'));
        foreach (['user', 'order*', 'payment_method_*_saved_card_billing_address', 'otp'] as $pattern) {
            $this->assertContains($pattern, $tables('personal_data'));
        }
        foreach (['authentication_*_access_token', 'payment_method_*_user', 'maintenance_mode_whitelist'] as $pattern) {
            $this->assertContains($pattern, $tables('environment'));
        }
        // Left out until each environment seeds its own integration clients
        $this->assertNotContains('integration_client', $tables('environment'));
        $this->assertSame(['/sitemap-*.xml', '/sitemap.xml'], $this->snapshotConfig->expandAssetGroups(['@generated']));
    }

    public function testCoreAndThePurposeGroupsAreDeprecated(): void
    {
        $this->assertSame(['core', 'customer_uploads'], array_keys($this->snapshotConfig->deprecatedAssetGroups));
        $this->assertSame(
            ['core', 'customers', 'sales', 'payment', 'admin', 'auth', 'logs'],
            array_keys($this->snapshotConfig->deprecatedDatabaseTableGroups)
        );
    }
}
