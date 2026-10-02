<?php

/**
 * Snapshot groups for the clicktap middleware. A plan references them as `@name` in an asset's or a
 * database's `excludes`. An application adds its own paths or tables in a group of its own and lists
 * it next to `@core`; it never redefines a group below, because application config is laid over this
 * with array_replace_recursive, which replaces list entries by position.
 */
return [
    'snapshot' => [
        // Paths under public/assets and public/media, rsync-style; a leading `/` anchors at the root
        'asset_groups' => [
            // What a scrubbed snapshot, and every deploy that restores from one, leaves out
            'core' => [
                '@generated',
                '@customer_uploads',
            ],
            // Rebuilt by the app: the per-view sitemaps `seo:sitemap:generate` writes into
            // public/assets, and the aggregate sitemap.xml older releases wrote, which may linger in
            // either directory. A copy would carry the source environment's URLs.
            'generated' => [
                '/sitemap.xml',
                '/sitemap-*.xml',
            ],
            // Customer uploads: none today, as both directories hold catalog, CMS and blog content.
            // When a module starts writing a customer-data tree there, it goes here.
            'customer_uploads' => [],
        ],
        // Table names, fnmatch patterns
        'database_table_groups' => [
            // What a scrubbed snapshot leaves out
            'core' => [
                '@customers',
                '@sales',
                '@payment',
                '@admin',
                '@auth',
                '@logs',
                '@environment',
            ],
            // Customer accounts and B2B companies and their members, addresses, newsletter
            // subscriptions, stock-alert emails
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
            // Orders, carts and checkout results
            'sales' => [
                'order*',
                'quote*',
                'historical_quote*',
                'checkout_success',
            ],
            // Saved cards, their billing addresses and the gateway customer ids, for every payment
            // provider. Scoped so payment_method_*_attribute* configuration still ships.
            'payment' => [
                'payment_method_*_saved_card',
                'payment_method_*_saved_card_billing_address',
                'payment_method_*_user',
            ],
            'admin' => [
                'admin_user*',
                'admin_role_assignment',
            ],
            // Access and refresh tokens for every area, and one-time passwords, which carry the
            // customer email or phone
            'auth' => [
                'authentication_*_access_token',
                'authentication_*_refresh_token',
                'otp',
            ],
            // Run history, and the permission audit trail, which carries user emails and request IPs
            'logs' => [
                'integration_run*',
                'scheduler_job_run',
                'authorization_*_permission_event',
            ],
            // The source environment's own settings: the maintenance-mode IP allowlist
            'environment' => [
                'maintenance_mode_whitelist',
            ],
        ],
    ],
];
