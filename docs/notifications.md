# Notifications

Home OS currently supports a daily email digest pipeline for local/dev use.

## What The Digest Includes

- Overdue reminders.
- Reminders due today.
- Overdue home maintenance tasks.
- Documents expiring soon.
- Imported finance rows needing review.
- Critical health review items.

## Local Mail Delivery

Docker Compose runs Mailpit for local email capture:

- Web UI: `http://localhost:8025`
- SMTP: `smtp://mailpit:1025`

The backend uses:

```text
MAILER_DSN=smtp://mailpit:1025
NOTIFICATION_SENDER_EMAIL=home-os@example.test
```

Do not commit real SMTP credentials. Use local environment overrides for real providers later.

## Commands

Preview without sending:

```sh
docker compose exec -T backend php bin/console homeos:send-daily-digest --dry-run
```

Send to enabled household users through the configured mailer:

```sh
docker compose exec -T backend php bin/console homeos:send-daily-digest
```

Target one household:

```sh
docker compose exec -T backend php bin/console homeos:send-daily-digest --household=<household-id>
```

## Preferences

Digest preferences are stored per user account:

- `notification_digest_enabled`: defaults to enabled.
- `notification_digest_hour`: optional hour from `0` to `23`; when empty, the user can receive the digest whenever the command runs.

There is no UI for these preferences yet.

## Local Cron Example

Run daily at 07:00:

```cron
0 7 * * * cd "/Users/damianprivate/Documents/my home project" && docker compose exec -T backend php bin/console homeos:send-daily-digest >/tmp/home-os-digest.log 2>&1
```

There is no background scheduler in the app yet. Cron is the recommended local MVP.
