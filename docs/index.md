Conductor Clicktap Platform Support Documentation
=================================================

This module adds the `clicktap` platform to [Conductor](https://github.com/conductorphp/conductor-core):
the Clicktap middleware, a Mezzio application. It ships the snapshot groups the middleware's plans
reference, so a fix to them reaches every application on its next `composer update` instead of being
copied into each one.

## Installation

```bash
composer require conductor/clicktap-platform-support
```

Add `ConductorClicktapPlatformSupport\ConfigProvider` to your Conductor config aggregator, and set
the application's platform:

```yaml
application_orchestration:
  application:
    platform: clicktap
```

The package overrides no services: the application keeps Conductor's default maintenance strategy
and code-deployment state, as with `platform: custom`. Its plans put the application into maintenance
mode with their own steps.

## Snapshot groups (CTAP-2161)

A plan references a group as `@name` in an asset's or a database's `excludes` list. Each group holds
one **nature** of data, so a plan names what it leaves out and why. A table can be of several natures,
so it can sit in several groups; a plan listing several groups gets each table once.

| Group | Assets (`public/assets`, `public/media`) | Tables |
|---|---|---|
| `@generated` | `/sitemap.xml`, `/sitemap-*.xml`: rebuilt by the app, and carry the source environment's URLs | `authorization_*_permission_snapshot`: rebuilt at sign-in |
| `@cache` | Nothing yet | `quote_cache`, `quote_cache_entity` |
| `@scratch` | Nothing yet | `integration_run*`, `scheduler_job_run`, `authorization_*_permission_event`, `rate_limit_counter`, `rate_limit_lock`, `event_log`, `event_log_account`, `event_log_tenant`, `payment_method_stripe_webhook_event`, `catalog_price_rule_dirty_product` |
| `@personal_data` | `/order/shipment-labels`, `/personalization/uploads`: under the shared root, so only a plan that syncs the whole root reaches them | `user`, `user_website`, `admin_user*`, `admin_role_assignment`, `company`, `company_unit`, `company_user`, `address_book*`, `newsletter_subscription`, `product_alert_stock`, `order*`, `quote*`, `historical_quote*`, `checkout_success`, `payment_method_*_saved_card`, `payment_method_*_saved_card_billing_address`, `payment_method_*_user`, `payment_user_preferred_method`, `otp`, `authorization_*_permission_event`, `authorization_*_permission_snapshot`, `role_assignment`, `account`, `account_credit_balance`, `account_credit_entry`, `account_user_default`, `account_user_membership`, `account_role_assignment`, `account_global_role_assignment`, `company_order`, `company_unit_default`, `company_unit_membership`, `event_log`, `event_log_account`, `event_log_tenant`, `payment_stored_method`, `payment_method_stripe_customer`, `payment_method_stripe_webhook_event`, `personalization_artwork`, `personalization_artwork_correction`, `personalization_configuration`, `subscription`, `subscription_change`, `subscription_charge`, `subscription_event`, `subscription_notice`, `subscription_period` |
| `@environment` | Nothing yet | `authentication_*_access_token`, `authentication_*_refresh_token`, `otp`, `admin_user*`, `payment_method_*_saved_card`, `payment_method_*_user`, `payment_stored_method`, `payment_method_stripe_customer`, `authentication_admin_session_reauthentication`, `tenant_catalog`, `maintenance_mode_whitelist` |

Asset patterns are rsync-style (a leading `/` anchors at the directory root); table patterns are
`fnmatch` patterns.

`integration_client`, `integration_client_account_binding` and `integration_role_assignment` belong
in `@environment`, but are left out until each environment seeds its own integration clients: without them, integrations stop authenticating after
a restore.

A **scrubbed seed** for lower environments:

```yaml
excludes: [ '@generated', '@cache', '@scratch', '@personal_data', '@environment' ]
```

A **media backup** keeps customer files: `excludes: [ '@generated', '@cache', '@scratch' ]`.

Some tables hold source credentials but are kept, because the app breaks without them:
`payment_method`, `shipping_carrier`, `tax_provider`, `system_setting`,
`authentication_frontend_provider` and `integration_connector_system_instance`. Each environment
overrides them in `environment-settings.sql`. `data_patch_applied` and `sequence` must travel with
the data.

### Adding your own

Put your application's own paths or tables in a group of your own, and list it next to the
package's groups in your plans:

```yaml
application_orchestration:
  application:
    snapshot:
      database_table_groups:
        project:
          - 'rma*'
```

```yaml
excludes: [ '@generated', '@cache', '@scratch', '@personal_data', '@environment', '@project' ]
```

Do not redefine a group this package ships. Application config is laid over platform config with
`array_replace_recursive`, which replaces list entries by position, so a redefined group silently
mixes your entries with the package's.

### Deprecated groups

The 1.0 groups keep working and expand exactly as before. A plan that names one logs a warning
saying what to use instead (with `conductor/application-orchestration` 4.7 or later). They are
removed in the next major.

| Group | Use instead |
|---|---|
| assets `@core` | `@generated`, `@cache`, `@scratch`, `@personal_data`, `@environment` |
| `@customer_uploads` | `@personal_data` |
| tables `@core` | `@generated`, `@cache`, `@scratch`, `@personal_data`, `@environment` |
| `@customers`, `@sales` | `@personal_data` |
| `@payment`, `@admin` | `@personal_data` and `@environment` |
| `@auth` | `@environment` |
| `@logs` | `@scratch` |

`@environment` was a 1.0 group holding only `maintenance_mode_whitelist`; it is now the nature
group, which also holds the tokens and credentials. `@core` lists `maintenance_mode_whitelist`
itself, so it is unchanged.
