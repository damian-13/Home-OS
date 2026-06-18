# MVP Roadmap

## Phase 1: Foundation

- Lock DDD/CQRS folder structure.
- Add household and member model.
- Add email/password login and logout.
- Add PostgreSQL migrations.
- Add shared API client.
- Add dashboard shell with navigation.

## Phase 2: Manual Data Capture

- Add Polish/English expense categories and manual PLN expense entry.
- Track which household member paid an expense.
- Add general health profiles.
- Add lab marker catalog and manual lab result entry.
- Add home maintenance reminders.
- Add document metadata without file parsing.

## Phase 3: Files and Reports

- Upload receipts, lab PDFs, contracts, and manuals.
- Link documents to expenses, health results, and home tasks.
- Add monthly expense report.
- Add health trend charts.
- Add upcoming maintenance view.

## Phase 4: Automation

- Import bank CSV exports.
- Parse lab result PDFs.
- Add email reminders.
- Add backup/export workflow.
- Integrate with Home Assistant for device status first.

## Phase 5: Polish and Hardening

- Add audit logs.
- Add encrypted file storage.
- Add role/permission model only if it becomes necessary.
- Add mobile-friendly views.
- Add tests for critical health and finance flows.
