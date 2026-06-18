# Architecture

## Stack

- Backend: Symfony 8.1, PHP 8.5, API Platform or Symfony controllers for JSON APIs.
- Frontend: React, TypeScript, Vite, React Router, TanStack Query.
- Database: PostgreSQL.
- Auth: Symfony Security with session auth for local use, or JWT if mobile/remote clients become important.
- Files: local encrypted file storage first, cloud/object storage later if needed.
- Background jobs: Symfony Messenger.

## Repository Layout

```text
backend/          Symfony API application
frontend/         React application
docs/             Product and technical notes
tools/docker/     Development containers
data/             Local-only data, ignored by git
```

## Domain Boundaries

Each domain should own its own entities, validation, and workflows:

- Home: rooms, devices, maintenance schedules, inventory.
- Finance: expenses, categories, accounts, recurring payments, receipts.
- Health: lab tests, results, reference ranges, symptoms, appointments.
- Documents: file metadata, tags, relationships to other domains.
- Reminders: generic due dates and recurrence linked to any domain item.

Shared concepts should stay small:

- User
- Attachment
- Reminder
- AuditLog
- Tag

## Backend Shape

Use Symfony as an API backend:

- Entities and migrations live in `backend/src`.
- API endpoints return JSON.
- Validation happens on DTOs or request models before persistence.
- Domain services handle workflows like importing a blood test PDF or creating recurring reminders.
- Messenger handles slow work such as parsing documents, sending notifications, and syncing devices.

Suggested first endpoints:

```text
GET    /api/dashboard
GET    /api/expenses
POST   /api/expenses
GET    /api/health/lab-results
POST   /api/health/lab-results
GET    /api/home/maintenance
POST   /api/home/maintenance
GET    /api/documents
POST   /api/documents
```

## Frontend Shape

React should be organized by feature:

```text
frontend/src/app/          app shell, routes, providers
frontend/src/features/     dashboard, expenses, health, home, documents
frontend/src/shared/       api client, UI primitives, formatting helpers
```

Use TanStack Query for server state. Keep forms feature-local until the same pattern repeats enough to deserve shared components.

## Security Notes

- Never commit real `.env` files.
- Store uploaded medical and financial files outside the public web root.
- Add role checks before exposing admin or integration settings.
- Keep audit logs for edits/deletes of health and financial records.
- Prefer explicit export features over direct database access for backups.
