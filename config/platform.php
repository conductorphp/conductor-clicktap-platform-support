<?php

/**
 * Snapshot groups for the clicktap middleware, by the nature of the data (CTAP-2161). A plan
 * references them as `@name` in an asset's or a database's `excludes`, and lists the natures it leaves
 * out. A table or path can be of several natures, so it can sit in several groups.
 *
 * An application adds its own paths or tables in a group of its own and lists it next to these; it
 * never redefines a group below, because application config is laid over this with
 * array_replace_recursive, which replaces list entries by position.
 */
return [
    'snapshot' => [
        // Paths under public/assets and public/media, rsync-style; a leading `/` anchors at the root
        'asset_groups' => [
            // Rebuilt by the app: the per-view sitemaps `seo:sitemap:generate` writes into
            // public/assets, and the aggregate sitemap.xml older releases wrote, which may linger in
            // either directory. A copy would carry the source environment's URLs.
            'generated' => [
                '/sitemap.xml',
                '/sitemap-*.xml',
            ],
            'cache' => [],
            'scratch' => [],
            // Customer uploads: none today, as both directories hold catalog, CMS and blog content.
            // When a module starts writing a customer-data tree there, it goes here.
            'personal_data' => [],
            'environment' => [],

            // Deprecated (CTAP-2161): see deprecated_asset_groups. They expand exactly as before.
            'core' => [
                '@generated',
                '@customer_uploads',
            ],
            'customer_uploads' => [],
        ],
        // Table names, fnmatch patterns
        'database_table_groups' => [
            'generated' => [],
            'cache' => [],
            // Short-lived: integration and scheduler run history, the permission audit trail, and
            // rate-limit counters
            'scratch' => [
                'integration_run*',
                'scheduler_job_run',
                'authorization_*_permission_event',
                'rate_limit_counter',
                'rate_limit_lock',
            ],
            // Relates to a person: users and admin users, B2B companies and their members, addresses,
            // newsletter subscriptions, stock-alert emails, orders, carts and checkout results, saved
            // cards and their billing addresses, one-time passwords (they carry the email or phone),
            // and the permission audit trail (user emails and request IPs)
            'personal_data' => [
                'user',
                'user_website',
                'admin_user*',
                'admin_role_assignment',
                'company',
                'company_unit',
                'company_user',
                'address_book*',
                'newsletter_subscription',
                'product_alert_stock',
                'order*',
                'quote*',
                'historical_quote*',
                'checkout_success',
                'payment_method_*_saved_card',
                'payment_method_*_saved_card_billing_address',
                'payment_method_*_user',
                'otp',
                'authorization_*_permission_event',
            ],
            // Belongs to the source environment: access and refresh tokens for every area, one-time
            // passwords, admin credentials, payment gateway card and customer ids, and the maintenance
            // allowlist. `integration_client` belongs here too, but is left out until each environment
            // seeds its own clients: without them, integrations stop authenticating after a restore.
            'environment' => [
                'authentication_*_access_token',
                'authentication_*_refresh_token',
                'otp',
                'admin_user*',
                'payment_method_*_saved_card',
                'payment_method_*_user',
                'maintenance_mode_whitelist',
            ],

            // Deprecated (CTAP-2161): see deprecated_database_table_groups. They expand exactly as
            // before; @core lists maintenance_mode_whitelist itself, as @environment now means more.
            'core' => [
                '@customers',
                '@sales',
                '@payment',
                '@admin',
                '@auth',
                '@logs',
                'maintenance_mode_whitelist',
            ],
            'customers' => [
                'user',
                'user_website',
                'address_book*',
                'company',
                'company_unit',
                'company_user',
                'newsletter_subscription',
                'product_alert_stock',
            ],
            'sales' => [
                'order*',
                'quote*',
                'historical_quote*',
                'checkout_success',
            ],
            'payment' => [
                'payment_method_*_saved_card',
                'payment_method_*_saved_card_billing_address',
                'payment_method_*_user',
            ],
            'admin' => [
                'admin_user*',
                'admin_role_assignment',
            ],
            'auth' => [
                'authentication_*_access_token',
                'authentication_*_refresh_token',
                'otp',
            ],
            'logs' => [
                'integration_run*',
                'scheduler_job_run',
                'authorization_*_permission_event',
            ],
        ],
        // Groups kept for compatibility (CTAP-2161). A plan that names one gets a warning with what to use
        // instead; they are removed in the next major.
        'deprecated_asset_groups' => [
            'core' => 'Use @generated, @cache, @scratch, @personal_data and @environment.',
            'customer_uploads' => 'Use @personal_data.',
        ],
        'deprecated_database_table_groups' => [
            'core' => 'Use @generated, @cache, @scratch, @personal_data and @environment.',
            'customers' => 'Use @personal_data.',
            'sales' => 'Use @personal_data.',
            'payment' => 'Use @personal_data and @environment.',
            'admin' => 'Use @personal_data and @environment.',
            'auth' => 'Use @environment.',
            'logs' => 'Use @scratch.',
        ],
    ],
];
