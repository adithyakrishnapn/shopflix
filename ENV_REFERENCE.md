# Environment Variables Reference (Why Each One Is Required)

This document explains why the environment variables in this project are needed.
It is written for this ShopFlix (Bagisto + Laravel) deployment.

## 1. Core Application Identity

- `APP_NAME`: Store/application display name used in notifications and templates.
- `APP_ENV`: Runtime mode (`local`, `staging`, `production`) that changes framework behavior.
- `APP_KEY`: Encryption key for sessions, cookies, and encrypted data. Mandatory in production.
- `APP_DEBUG`: Controls detailed error output. Must be `false` in production.
- `APP_DEBUG_ALLOWED_IPS`: Optional allowlist for debug access when debug is restricted.
- `APP_URL`: Base URL used to generate absolute links.
- `APP_ADMIN_URL`: Admin route prefix (for example `admin`).
- `APP_TIMEZONE`: Default timezone for date handling.
- `APP_LOCALE`: Default language locale.
- `APP_CURRENCY`: Base currency fallback for app-level defaults.
- `APP_FORCE_HTTPS`: Forces generated URLs to use `https` when required.
- `ASSET_URL`: Optional dedicated base URL for static assets.
- `PORT`: Runtime container port used by web server inside Docker.

## 2. Installation and Startup Controls

- `BAGISTO_INSTALLATION_TYPE`: Controls installer profile (for this project, `cream`).
- `BAGISTO_SEED_BASE_DATA`: Enables seeding base data automatically.
- `FORCE_FRESH_INSTALL`: Drops/rebuilds schema when explicitly requested.
- `FORCE_INSTALLER`: Re-opens installer flow by clearing install marker.

## 3. Logging

- `LOG_CHANNEL`: Where Laravel logs are written (`stack`, `stderr`, etc.).
- `LOG_SLACK_WEBHOOK_URL`: Optional webhook for alert delivery.

## 4. Database

- `DB_CONNECTION`: Database driver (`mysql`).
- `DB_HOST`: Database host/container.
- `DB_PORT`: Database port.
- `DB_DATABASE`: Database name.
- `DB_USERNAME`: Database user.
- `DB_PASSWORD`: Database password.
- `DB_PREFIX`: Optional table prefix.
- `DB_SOCKET`: Optional Unix socket path.
- `DB_SSLMODE`: SSL mode for managed MySQL services.

## 5. Cache, Session, Queue, Broadcast

- `BROADCAST_DRIVER`: Event broadcasting backend.
- `CACHE_DRIVER`: Cache backend used by app.
- `CACHE_CONNECTION`: Optional named cache connection.
- `SESSION_DRIVER`: Session storage backend.
- `SESSION_CONNECTION`: Optional session connection.
- `SESSION_STORE`: Optional named session store.
- `SESSION_LIFETIME`: Session expiry in minutes.
- `SESSION_DOMAIN`: Cookie domain for sessions.
- `SESSION_SECURE_COOKIE`: Enforces secure cookie over HTTPS only.
- `QUEUE_DRIVER`: Queue backend for async jobs.
- `QUEUE_FAILED_DRIVER`: Failed jobs persistence backend.

## 6. Redis and Memcached

- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`: Redis connection details.
- `REDIS_DEFAULT_DATABASE`, `REDIS_CACHE_DATABASE`, `REDIS_SESSION_DATABASE`: Logical DB split.
- `MEMCACHED_HOST`, `MEMCACHED_PORT`, `MEMCACHED_USERNAME`, `MEMCACHED_PASSWORD`, `MEMCACHED_PERSISTENT_ID`: Memcached support when used.

## 7. Response Cache Controls

- `RESPONSE_CACHE_ENABLED`: Enables full response caching.
- `RESPONSE_CACHE_DRIVER`: Storage backend for cached responses.
- `RESPONSE_CACHE_LIFETIME`: Default cache TTL.
- `RESPONSE_CACHE_HEADER_NAME`: Cache status header name.
- `RESPONSE_CACHE_AGE_HEADER`: Enables response age header.
- `RESPONSE_CACHE_AGE_HEADER_NAME`: Age header key.
- `CACHE_BYPASS_HEADER_NAME`, `CACHE_BYPASS_HEADER_VALUE`: Header-based cache bypass controls.

## 8. File Storage and Cloud

- `FILESYSTEM_DISK`: Default filesystem disk (usually `public`).
- `FILESYSTEM_CLOUD`: Cloud disk name when cloud storage is used.
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_URL`: S3-compatible storage config.

## 9. Email (Transport + Identity)

- `MAIL_MAILER`: Mail transport type (`smtp`, `log`, etc.).
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`: SMTP transport credentials.
- `MAIL_LOG_CHANNEL`: Log channel for log mailer.
- `MAIL_SENDMAIL_PATH`: Binary path for sendmail transport.
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`: Default sender identity.
- `ADMIN_MAIL_ADDRESS`, `ADMIN_MAIL_NAME`: Admin contact identity.
- `CONTACT_MAIL_ADDRESS`, `CONTACT_MAIL_NAME`: Contact identity used in templates.
- `MAILGUN_DOMAIN`, `MAILGUN_SECRET`, `SES_KEY`, `SES_SECRET`, `SES_REGION`, `SPARKPOST_SECRET`: Alternate provider credentials.

## 10. Realtime and Notifications

- `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER`: Pusher setup for realtime events.
- `MIX_PUSHER_APP_KEY`, `MIX_PUSHER_APP_CLUSTER`: Frontend exposure for build/runtime compatibility.

## 11. Social Login

- `FACEBOOK_*`, `TWITTER_*`, `GOOGLE_*`, `LINKEDIN_*`, `GITHUB_*`: OAuth client credentials and callback URLs.

## 12. Payments and External APIs

- `STRIPE_KEY`, `STRIPE_SECRET`: Stripe credentials when Stripe is enabled.
- `EXCHANGE_RATES_API_KEY`, `EXCHANGE_RATES_API_ENDPOINT`, `FIXER_API_KEY`: Currency conversion providers.
- `OPENAI_API_KEY`, `OPENAI_ORGANIZATION`, `OPENAI_REQUEST_TIMEOUT`: AI integration configuration.

## 13. Search and Queue Providers

- `ELASTICSEARCH_HOST`, `ELASTICSEARCH_USER`, `ELASTICSEARCH_PASS`, `ELASTICSEARCH_CLOUD_ID`, `ELASTICSEARCH_API_KEY`: Search backend config.
- `SQS_KEY`, `SQS_SECRET`, `SQS_PREFIX`, `SQS_QUEUE`, `SQS_REGION`: AWS SQS queue transport config.

## 14. Runtime/Server Extras

- `OCTANE_SERVER`, `OCTANE_HTTPS`: Laravel Octane runtime tuning.
- `SANCTUM_STATEFUL_DOMAINS`: Domains treated as stateful for SPA auth.
- `VITE_HOST`, `VITE_PORT`: Frontend dev server settings (usually empty in production).

## 15. Security Guidance

- Never commit real secrets to git.
- Keep production secrets in server-side `.env` or secret manager.
- Rotate keys immediately if exposed (payment keys, SMTP password, OAuth secrets).
- Use different credentials for local, staging, and production.

## 16. Minimum Required for Production Boot

At minimum, set these before first production boot:

- `APP_ENV`, `APP_KEY`, `APP_DEBUG`
- `APP_URL`, `ASSET_URL`, `APP_FORCE_HTTPS`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `LOG_CHANNEL`
- Mail transport keys (`MAIL_*`) if emails must work immediately

Without these, the app may start in a broken state (bad URL generation, DB failures, or mail failures).
