# Home OS Development Roadmap

This roadmap is the single source of truth for product direction and development priorities. It should be updated after every meaningful development session when scope, priorities, or completed milestones change.

## Summary

Home OS should become a private, local-first daily command center for family life: money, health, documents, home maintenance, routines, reminders, and decisions.

Every new feature should clearly satisfy at least one goal:

- save time,
- reduce stress,
- reduce mental load,
- automate repetitive work,
- prevent forgotten important things,
- improve household decisions,
- keep important information organized,
- make the app worth opening every day.

Before implementing a feature, use this decision check:

- Does this make daily life easier?
- Will it be used every week?
- Does it reduce manual work?
- Is there a simpler implementation?
- Is it worth the maintenance cost?
- Does it fit the long-term vision?

If the answer is no, do not implement it yet.

## 1. Current State

### Implemented

- Authentication with email/password, session-based current user, household/member model.
- Dashboard with real finance/health attention items and quick daily command center.
- Expenses module: manual expenses, categories, income, budgets, recurring bills, monthly bill payments, import review, saved review rules, undo batches, analytics, and monthly review.
- Health module: blood tests, marker history, out-of-range detection, health documents, lab result extraction/parsing flow.
- Local Docker development setup with Symfony backend, React/Vite frontend, and PostgreSQL.

### Working Well

- Backend is already organized by domain with DDD/CQRS-style commands, queries, handlers, repositories, and thin controllers.
- Expenses has real daily value and useful review workflows.
- Dashboard is no longer static and already surfaces finance/health attention.
- Health import flow is practical for historical lab results.

### Incomplete

- Home maintenance is still placeholder.
- Generic Documents module does not exist; documents are currently health-specific.
- Generic reminders/tasks do not exist.
- Dashboard cannot yet show home/document/reminder signals.
- No real notification pipeline.
- No backup/export workflow.
- No mobile-specific UX beyond responsive CSS.

### Technical Debt

- `frontend/src/App.tsx` and `frontend/src/App.css` are too large and should be split by feature.
- Architecture docs mention React Router and TanStack Query, but the app still uses hash routing and local `useState` fetches.
- No automated backend or frontend tests are present.
- Shared concepts from docs, such as Attachment, Reminder, AuditLog, and Tag, are not implemented.
- File storage is local but not encrypted.
- Imported finance data exists locally, but bank import UI is not implemented.

### Missing Infrastructure

- Test harness for Symfony API flows.
- Frontend test setup.
- CI checks.
- Audit logging for sensitive health/finance changes.
- Background jobs via Messenger for parsing, reminders, notifications, and future integrations.
- Backup/export and restore path.
- Security hardening for uploaded files.

### Missing UX

- Home page as a real daily module.
- Document archive with expiry reminders.
- Health review queue.
- Better onboarding/default data setup.
- Mobile bottom navigation and mobile-first quick entry.
- Notification preferences.
- Global search.

## 2. Long-Term Vision

In 1-2 years, Home OS should be the family private daily operating system:

- Open daily to see what needs attention today, soon, and overdue.
- Capture expenses, documents, health results, and home tasks in seconds.
- Review imported/parsed data instead of manually entering everything.
- Predict forgotten tasks: expiring documents, unpaid bills, stale health checks, recurring maintenance.
- Connect domains: a warranty document belongs to a device, a medical PDF belongs to a health result, a recurring bill belongs to finance and reminders.
- Work locally first, with explicit backup/export and optional integrations.
- Support a future mobile app through stable household-aware APIs.
- Use AI only where it reduces effort: parsing, categorization, summarization, anomaly detection, and smart reminders.

## 3. Development Phases

| Phase | Purpose | User Value | Dependencies | Complexity |
|---|---|---:|---|---|
| 1. Stabilize Foundation | Add tests, roadmap governance, frontend structure, basic DX | High | Current code | Medium |
| 2. Core Daily Usage | Make Dashboard, quick capture, reminders, home tasks useful daily | Very High | Foundation | Medium |
| 3. Home Maintenance | Track recurring maintenance, warranties, inspections, household routines | Very High | Reminders | Medium |
| 4. Documents MVP | Generic document metadata, expiry dates, tags, linked files | Very High | Attachment/storage | Medium |
| 5. Finance Automation | Bank import UI, saved rule auto-apply, category learning, monthly close | Very High | Current Expenses | Medium/High |
| 6. Health Intelligence | Health review queue, marker cleanup, stale checks, appointments, medications | High | Current Health | Medium/High |
| 7. Family Productivity | Shared routines, chores, decisions, shopping/home lists | Medium/High | Reminders/tasks | Medium |
| 8. Notifications | Email reminders, daily digest, overdue warnings | High | Reminders + Messenger | Medium |
| 9. Security & Reliability | Audit logs, encrypted files, backup/export, permissions later if needed | Very High | Stable domains | High |
| 10. Integrations & AI | OCR, Home Assistant, calendar, AI assistant, predictive reminders | High later | Stable core data | High |

## 4. Prioritized Backlog

| Priority | Item | Why It Matters | Effort | Impact |
|---|---|---|---:|---:|
| P0 | Add `docs/development-roadmap.md` and update `AGENTS.md` | Makes roadmap the single source of truth | S | High |
| P0 | Add backend API tests for Dashboard, Expenses review, Health import | Protects sensitive core flows | M | Very High |
| P0 | Split frontend into feature folders without changing behavior | Makes future work faster and safer | M | High |
| P0 | Home Maintenance MVP | Fills biggest MVP gap and makes Dashboard useful beyond finance/health | M | Very High |
| P0 | Generic Reminder model | Enables home tasks, document expiry, health follow-ups, bill reminders | M | Very High |
| P1 | Generic Documents MVP | Organizes contracts, invoices, warranties, manuals, IDs | M | Very High |
| P1 | Dashboard signals for home tasks and document expiry | Turns Dashboard into true daily start page | S/M | Very High |
| P1 | Health Review Center | Reduces stress around imported/unknown/out-of-range markers | M | High |
| P1 | Bank import UI | Removes manual developer-assisted imports | M/H | Very High |
| P1 | Saved finance rules auto-apply on import | Reduces repetitive review work | M | High |
| P1 | Mobile-first navigation and quick actions | Makes daily capture realistic | M | High |
| P2 | Audit logs for finance/health/documents | Needed for trust and safety | M | High |
| P2 | Backup/export workflow | Protects local-first data | M/H | Very High |
| P2 | Email reminder digest | Prevents forgotten tasks without app checking | M | High |
| P2 | Document file security hardening | Protects sensitive files | M | High |
| P3 | Calendar integration | Useful after reminders/tasks are stable | H | Medium/High |
| P3 | Home Assistant status integration | Valuable later, not before home/domain basics | H | Medium |
| P3 | AI/OCR assistant | Powerful after data model and review flows mature | H | High later |

## 5. Milestones

### Milestone 1: Roadmap + Safety Net

- Create `docs/development-roadmap.md`.
- Link it from `README.md` and `AGENTS.md`.
- Add backend test setup and first tests for login, dashboard, expense overview, health overview.
- Acceptance: new work has a known roadmap location and critical APIs have at least smoke coverage.

### Milestone 2: Home Maintenance MVP

- Add Home domain with maintenance tasks.
- API: list/create/update/delete/mark done.
- Fields: title, area, due date, recurrence, assigned member, notes, status.
- Dashboard: show overdue/upcoming home tasks.
- Frontend: real `#home` page or replace placeholder with Home section.
- Acceptance: user can track recurring home tasks and see due work on Dashboard.

### Milestone 3: Generic Reminders

- Add household reminders with due date, optional recurrence, related type/id, completed/skipped status.
- Use reminders for home tasks first.
- Dashboard attention: overdue today/upcoming.
- Acceptance: reminders become reusable infrastructure without overbuilding notifications yet.

### Milestone 4: Documents MVP

- Add generic documents domain separate from health documents.
- Metadata: title, type, owner member, issue date, expiry date, tags, note, file.
- Dashboard attention: expiring/expired documents.
- Acceptance: contracts, warranties, invoices, manuals can be stored and found.

### Milestone 5: Health Review Center

- Add a dedicated Health review section for unknown markers, suspicious references, out-of-range results, stale markers.
- Add fast cleanup/edit actions.
- Acceptance: imported lab data can be trusted without scanning raw tables.

### Milestone 6: Finance Import Productization

- Add import UI for bank file upload/preview.
- Auto-apply saved finance rules.
- Review only uncertain transactions.
- Acceptance: finance import no longer requires manual database/dev work.

### Milestone 7: Mobile Daily Use

- Add mobile-friendly navigation and quick action layout.
- Prioritize Dashboard, quick expense, reminders, health review.
- Acceptance: user can complete daily capture/review comfortably on phone.

### Milestone 8: Reliability & Privacy

- Add audit logs for sensitive edits/deletes.
- Add backup/export.
- Harden uploaded file storage.
- Add CI checks.
- Acceptance: app is safer to trust with real personal data.

## 6. Technical Improvements

### Architecture

- Keep backend DDD/CQRS, but avoid introducing generic abstractions until two domains need the same behavior.
- Introduce Shared `Reminder` only when Home/Documents need it.
- Introduce Shared `Attachment` when Documents becomes generic; health documents can migrate later.
- Keep session auth for local web, design APIs so JWT/mobile auth can be added later.

### Frontend

- Split `App.tsx` into `features/dashboard`, `features/expenses`, `features/health`, `features/household`, `features/documents`, `shared/api`, `shared/format`.
- Keep hash routing until feature extraction is done; then consider React Router.
- Do not add TanStack Query until API calls become hard to reason about after extraction.

### Testing

- Backend first: API tests for auth, household access, expenses overview/review, dashboard attention, health import/review.
- Frontend next: build check, lightweight component tests for dashboard actions, Playwright smoke flows later.
- Add fixture builders for household, expenses, health results.

### Security

- Keep files outside public web root.
- Add file type/size validation and safer download headers.
- Add audit logs for health, finance, documents.
- Add backup/export before relying on app as source of truth.
- Roles remain out of scope until more than one access level is genuinely needed.

### Performance

- Watch dashboard query growth; it currently aggregates Expenses and Health.
- Add targeted indexes when Home/Documents/Reminders add due-date queries.
- Avoid expensive per-marker history loops if health data grows; replace with repository-level latest-marker query later.

### Developer Experience

- Add `make check` for backend container lint, schema validation, frontend build.
- Add `make test` once tests exist.
- Keep AGENTS and roadmap synchronized.

## 7. UX Improvements

### Dashboard

- Make Dashboard the default daily start.
- Show Today, Upcoming, Overdue, Review Queues, Recent Activity.
- Keep actions one click away: add expense, review imports, mark bill paid, mark task done, review health.

### Navigation

- Add real Home page.
- Keep main navigation small: Dashboard, Home, Expenses, Health, Documents.
- Use internal tabs only inside complex modules.

### Forms

- Default forms collapsed except fast capture.
- Use smart defaults: current user/member, today, last used category, current month.
- Add inline validation and clear save states.

### Mobile

- Add bottom nav or compact side nav.
- Prioritize large tap targets and one-column forms.
- Dashboard quick actions should be usable with one hand.

### Accessibility

- Ensure form labels, button names, focus states, and contrast are consistent.
- Avoid native-looking unstyled selects/buttons.
- Ensure tables/cards do not overflow on small screens.

### Onboarding

- Seed household categories and example home maintenance templates.
- Add first-run checklist: add family member, add recurring income, add bill, add home task, upload document.

### Notifications

- Start with email digest: today due, overdue, upcoming soon.
- Add per-reminder notification later only if digest is not enough.

## 8. Future Ideas To Remember

- OCR for receipts and documents.
- AI lab result summarizer with strict medical disclaimers and source references.
- AI finance categorization suggestions, always reviewable.
- Predictive monthly cashflow warnings.
- Smart reminders based on patterns.
- Calendar integration for appointments, renewals, and recurring tasks.
- Home Assistant read-only status first, control later.
- Inventory and warranty linking to documents.
- Shopping/home supplies list based on repeated spending.
- Family decision log for important household choices.
- Encrypted local storage and automated encrypted backups.
- Mobile app using the same API.
- Global search across documents, expenses, health, and home tasks.

## Public Interfaces / Types

No immediate API change is required to create this roadmap document.

Future milestones should introduce these interfaces incrementally:

- `GET /api/households/{householdId}/home/maintenance-tasks`
- `POST /api/households/{householdId}/home/maintenance-tasks`
- `PATCH /api/households/{householdId}/home/maintenance-tasks/{taskId}`
- `DELETE /api/households/{householdId}/home/maintenance-tasks/{taskId}`
- `POST /api/households/{householdId}/home/maintenance-tasks/{taskId}/complete`
- `GET /api/households/{householdId}/documents`
- `POST /api/households/{householdId}/documents`
- `PATCH /api/households/{householdId}/documents/{documentId}`
- `DELETE /api/households/{householdId}/documents/{documentId}`
- `GET /api/households/{householdId}/reminders`
- `POST /api/households/{householdId}/reminders`
- `PATCH /api/households/{householdId}/reminders/{reminderId}`
- `DELETE /api/households/{householdId}/reminders/{reminderId}`
- Expand `GET /api/dashboard` summary and attention items to include home tasks, reminders, and document expiry.

## Test Plan

- Roadmap artifact: verify `docs/development-roadmap.md`, `README.md`, and `AGENTS.md` all point to the same roadmap.
- Backend smoke: auth, household access, dashboard, expense overview, health overview.
- Expenses: add/edit/delete expense, import review rule, undo batch, budget warning, overdue bill attention.
- Health: upload document, extract markers, import blood test, out-of-range attention, stale marker attention.
- Future Home: create task, mark done, recurrence creates next due task or computes next due date, dashboard shows overdue/upcoming.
- Future Documents: upload metadata/file, expiry warning, household access protection.
- Frontend: production build, dashboard actions navigate correctly, mobile viewport layout stays usable.

## Assumptions

- Roadmap language is English to match current docs and UI.
- The canonical roadmap file is `docs/development-roadmap.md`.
- No roles are needed in MVP.
- PLN remains the only currency for now.
- Local-first remains the default; cloud integrations are optional later.
- Email is the first notification channel.
- Home Assistant integration waits until Home/Reminders/Documents are useful without smart home.
- AI features wait until deterministic capture/review/reminder workflows are stable.

## Continuous Planning Rules

At the end of every development session:

1. Summarize what was completed.
2. Update this roadmap if priorities, scope, or milestone status changed.
3. Identify blockers.
4. Recommend the next highest-value task.
5. Explain why it should be implemented next.

Whenever new functionality is implemented:

- update this roadmap if necessary,
- keep the roadmap synchronized with the repository,
- avoid overengineering,
- prefer iterative improvements,
- keep backward compatibility,
- do not introduce unnecessary abstractions,
- implement only features that clearly improve the product.
