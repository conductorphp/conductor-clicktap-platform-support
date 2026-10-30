<?php

namespace ConductorClicktapPlatformSupportTest;

use ConductorAppOrchestration\Config\SnapshotConfig;
use ConductorClicktapPlatformSupport\ConfigProvider;
use PHPUnit\Framework\TestCase;

/**
 * CTAP-3037. Tables a consumer's scrubbed dumps carried PII in, matched by the nature groups the way the
 * export applies them: fnmatch against the real middleware table names.
 */
class DatabaseTableGroupsTest extends TestCase
{
    private SnapshotConfig $snapshotConfig;

    public function setUp(): void
    {
        $platform = (new ConfigProvider())()['application_orchestration']['platforms']['clicktap'];
        $this->snapshotConfig = new SnapshotConfig($platform['snapshot']);
    }

    /** subject_code is the customer's or staff member's email; sign-in rebuilds the snapshot. */
    public function testThePermissionSnapshotsArePersonalDataAndGenerated(): void
    {
        foreach (['personal_data', 'generated'] as $group) {
            foreach (['admin', 'frontend', 'integration'] as $area) {
                $this->assertExcludedBy($group, "authorization_{$area}_permission_snapshot");
            }
        }
    }

    public function testTheUsersPreferredPaymentMethodIsPersonalData(): void
    {
        $this->assertExcludedBy('personal_data', 'payment_user_preferred_method');
    }

    /** The tables the clicktap modules classify, as the monorepo's snapshot data-class audit reported them. */
    public function testTheModuleTablesAreInTheirNatureGroups(): void
    {
        $expected = [
            'personal_data' => [
                'account', 'account_credit_balance', 'account_credit_entry', 'account_user_default',
                'account_user_membership', 'account_role_assignment', 'account_global_role_assignment',
                'company_order', 'company_unit_default', 'company_unit_membership', 'role_assignment',
                'event_log', 'event_log_account', 'event_log_tenant',
                'payment_stored_method', 'payment_method_stripe_customer', 'payment_method_stripe_webhook_event',
                'personalization_artwork', 'personalization_artwork_correction', 'personalization_configuration',
                'subscription', 'subscription_change', 'subscription_charge', 'subscription_event',
                'subscription_notice', 'subscription_period',
            ],
            'environment' => [
                'payment_stored_method', 'payment_method_stripe_customer',
                'authentication_admin_session_reauthentication', 'tenant_catalog',
            ],
            'scratch' => [
                'event_log', 'event_log_account', 'event_log_tenant', 'payment_method_stripe_webhook_event',
                'catalog_price_rule_dirty_product',
            ],
            'cache' => ['quote_cache', 'quote_cache_entity'],
        ];
        foreach ($expected as $group => $tables) {
            foreach ($tables as $table) {
                $this->assertExcludedBy($group, $table);
            }
        }
    }

    /**
     * Definitions and settings stay in every copy: the restored app needs them, and environment-settings.sql
     * overrides the credentials among them. Integration clients stay until each environment seeds its own.
     */
    public function testTheNatureGroupsKeepDefinitionsAndSettings(): void
    {
        $natures = ['generated', 'cache', 'scratch', 'personal_data', 'environment'];
        foreach ([
            'payment_method', 'payment_method_attribute', 'account_role', 'account_global_role',
            'account_attribute', 'account_user_membership_attribute', 'account_user_membership_attribute_option',
            'event_log_event', 'personalization_template', 'personalization_field', 'subscription_plan',
            'subscription_plan_price', 'integration_client', 'integration_client_account_binding',
            'integration_role_assignment', 'system_setting', 'data_patch_applied', 'sequence',
        ] as $table) {
            foreach ($natures as $group) {
                $this->assertFalse($this->groupExcludes($group, $table), "@$group should keep $table.");
            }
        }
    }

    /** Relative to the shared root, which a plan syncing the whole root (the media backup) copies. */
    public function testPersonalDataCoversTheCustomerFileTrees(): void
    {
        $this->assertSame(
            ['/order/shipment-labels', '/personalization/uploads'],
            $this->snapshotConfig->expandAssetGroups(['@personal_data'])
        );
    }

    private function assertExcludedBy(string $group, string $table): void
    {
        $this->assertTrue($this->groupExcludes($group, $table), "@$group should exclude $table.");
    }

    private function groupExcludes(string $group, string $table): bool
    {
        return (bool) array_filter(
            $this->snapshotConfig->expandDatabaseTableGroups(["@$group"]),
            static fn(string $pattern): bool => fnmatch($pattern, $table)
        );
    }
}
