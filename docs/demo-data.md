# Demo Data

Home OS includes a local-only demo data seeder for testing the product with realistic volume before adding real private data.

## Load Demo Data

Run from the project root while Docker is running:

```sh
docker compose exec backend php bin/console homeos:seed-demo-data --reset-demo --months=12
```

For a larger finance dataset:

```sh
docker compose exec backend php bin/console homeos:seed-demo-data --reset-demo --months=12 --large
```

The command refuses to run in `prod`.

## Demo Login

Default credentials:

```text
homeos-demo-damian@example.test / password123
homeos-demo-partner@example.test / password123
homeos-demo-child@example.test / password123
```

These accounts are for local demo use only.

## Reset Demo Data

Recreate demo data:

```sh
docker compose exec backend php bin/console homeos:seed-demo-data --reset-demo
```

Remove demo data without recreating it:

```sh
docker compose exec backend php bin/console homeos:seed-demo-data --reset-demo --reset-only
```

Reset only deletes households tied to demo emails matching `homeos-demo-*@example.test` and household names containing `Demo`.

## What Is Included

- Demo household with Damian, Partner, and Child members.
- PLN expenses, income, budgets, recurring bills, bill payments, subscriptions, saved review rules, and imported rows needing review.
- Fake health blood tests with normal markers, out-of-range markers, duplicate-looking results, missing ranges, and suspicious OCR-style values.
- Home maintenance tasks that are overdue, due today, upcoming, recurring, and completed.
- Reminders that are overdue, due today, upcoming, recurring, completed, and skipped.
- Document metadata for insurance, warranty, invoice, contract, manual, tax, medical, passport, and other records.
- Dummy local document files generated under backend `var/` storage.
- Searchable examples such as `heat pump`, `LDL`, `mortgage`, `insurance`, `warranty`, `Allegro`, `Biedronka`, `passport`, `boiler`, and `internet`.
- Intentional Dashboard, Inbox, Health Review, Import Review, Timeline, Search, and Digest signals.

All health data is fake demo data and is not medical advice.
