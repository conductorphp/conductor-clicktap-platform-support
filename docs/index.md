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

## Snapshot groups (CTAP-2152)

A plan references a group as `@name` in an asset's or a database's `excludes` list. Groups compose:
list several, or mix them with literal names.

### Asset groups

For `public/assets` and `public/media`. Patterns are rsync-style; a leading `/` anchors at the
directory root.

| Group | Holds |
|---|---|
| `@generated` | `/sitemap.xml`, `/sitemap-*.xml`: sitemaps the app rebuilds, which carry the source environment's URLs |
| `@customer_uploads` | Nothing yet; the one place a customer-data tree goes |
| `@core` | `@generated` + `@customer_uploads`: what a scrubbed snapshot and the deploys that restore it leave out |

A full media backup excludes `@generated` only.

### Database table groups

`fnmatch` patterns against table names.

| Group | Tables |
|---|---|
| `@customers` | `user`, `user_website`, `address_book*`, `company`, `company_unit`, `company_user`, `newsletter_subscription`, `product_alert_stock` |
| `@sales` | `order*`, `quote*`, `historical_quote*`, `checkout_success` |
| `@payment` | `payment_method_*_saved_card`, `payment_method_*_saved_card_billing_address`, `payment_method_*_user` |
| `@admin` | `admin_user*`, `admin_role_assignment` |
| `@auth` | `authentication_*_access_token`, `authentication_*_refresh_token`, `otp` |
| `@logs` | `integration_run*`, `scheduler_job_run`, `authorization_*_permission_event` |
| `@environment` | `maintenance_mode_whitelist` |
| `@core` | all of the above: what a scrubbed snapshot leaves out |

### Adding your own

Put your application's own paths or tables in a group of your own, and list it next to `@core` in
your plans:

```yaml
application_orchestration:
  application:
    snapshot:
      database_table_groups:
        project:
          - 'rma*'
```

```yaml
excludes: [ '@core', '@project' ]
```

Do not redefine a group this package ships. Application config is laid over platform config with
`array_replace_recursive`, which replaces list entries by position, so a redefined group silently
mixes your entries with the package's.
