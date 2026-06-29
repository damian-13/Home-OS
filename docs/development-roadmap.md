# Home OS Development Roadmap

This roadmap is the single source of truth for product direction and development priorities. It should be updated after every meaningful development session when scope, priorities, or completed milestones change.

## Summary

Home OS should become a private, local-first personal operating system for family life: money, health, documents, home maintenance, routines, reminders, reviews, and decisions. It should not become a passive database. It should continuously help answer:

- What needs my attention?
- What should I do next?
- What can be automated?
- What can be simplified?
- What can be safely ignored?

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

- What real-life problem does this solve?
- How often will it realistically be used?
- Does it reduce manual work?
- Does it reduce stress?
- Does it save time?
- Is there a significantly simpler implementation?
- If this feature disappeared in one year, would it be missed?
- Is it worth the maintenance cost?
- Does it fit the long-term vision?

If the answer is no, do not implement it yet.

## 1. Current State

### Implemented

- Authentication with email/password, session-based current user, household/member model.
- Dashboard with real finance/health attention items and quick daily command center.
- Expenses module: manual expenses, categories, income, budgets, recurring bills, monthly bill payments, import review, saved review rules, undo batches, analytics, and monthly review.
- Health module: blood tests, marker history, out-of-range detection, health documents, lab result extraction/parsing flow.
- Home Maintenance MVP: one-time and recurring maintenance tasks with due dates, simple recurrence, completion, and Dashboard overdue/upcoming attention.
- Inbox + Daily Review MVP: thin aggregation of Expenses, Health, and Home review items with source navigation and Dashboard summary.
- Generic Reminders MVP: household reminders with due dates, simple recurrence, complete/skip, Dashboard attention, and Inbox/Daily Review integration.
- Generic Documents MVP: household document metadata, optional file upload/download, soft delete, expiry dates, Dashboard attention, and Inbox/Daily Review integration.
- Search + Timeline Foundation: read-only global search across implemented modules and deterministic household activity timeline.
- Health Review Center: deterministic health data-quality queue for out-of-range, unknown, suspicious, duplicate-looking, and stale lab data.
- Daily UX Stabilization: Dashboard Decision Center structure, global quick actions, Inbox filters, setup-focused empty states, grouped timeline spending, and lightweight frontend smoke coverage.
- Mobile Daily Use: phone-first bottom navigation, mobile quick action sheet, denser Dashboard/Inbox mobile layouts, mobile form spacing, and mobile smoke coverage.
- Reliability & Privacy MVP: audit logs, JSON household export, safer upload/download handling, and `make check`.
- Local Docker development setup with Symfony backend, React/Vite frontend, and PostgreSQL.

### Working Well

- Backend is already organized by domain with DDD/CQRS-style commands, queries, handlers, repositories, and thin controllers.
- Expenses has real daily value and useful review workflows.
- Dashboard is no longer static and is organized around Today, Quick Capture, Review, and Recent Activity.
- Health import flow is practical for historical lab results.

### Incomplete

- No real notification pipeline.
- Restore is still out of scope; export exists as JSON source-data backup.
- Attachment export is metadata-only for now; uploaded files remain downloadable through protected endpoints.

### Technical Debt

- `frontend/src/App.tsx` and `frontend/src/App.css` are still large, but Dashboard, Inbox, Search, Timeline, and Quick Actions have started moving into feature folders.
- Architecture docs mention React Router and TanStack Query, but the app still uses hash routing and local `useState` fetches.
- Backend smoke coverage and lightweight frontend smoke checks exist; broader automated API/component/browser coverage is still missing.
- Shared concepts from docs, such as a reusable Attachment model and richer Tag model, are not implemented.
- File storage is local and upload-hardened, but not encrypted.
- Audit logs exist for the MVP, but there is no audit log UI or reporting workflow yet.

### Missing Infrastructure

- Broader Symfony API test harness beyond the smoke script.
- Broader frontend test setup beyond lightweight source-level smoke checks.
- Hosted CI service wiring; local `make check` exists.
- Background jobs via Messenger for parsing, reminders, notifications, and future integrations.
- Restore path for JSON exports.
- Encrypted file storage and attachment-in-archive export.

### Missing UX

- Dashboard now acts as a first Decision Center, but Today prioritization should keep improving as more domains generate actions.
- Inbox exists for current Expenses, Health, Home, Reminders, and Documents signals, but does not yet cover future OCR failures, duplicates, AI suggestions, or persistent dismissals.
- Daily Review exists as a lightweight Inbox section, but Weekly Review remains future work.
- Better onboarding/default data setup.
- Mobile bottom navigation and mobile-first quick entry.
- Notification preferences.
- Search exists for implemented modules; ranking, richer filters, and future full-text indexing remain later work.

## 2. Long-Term Vision

In 1-2 years, Home OS should be the family private daily operating system:

- Open daily to see the highest-value actions for today, not just data summaries.
- Capture expenses, documents, health results, and home tasks in seconds.
- Review imported, parsed, OCR, and AI-suggested data in one Inbox instead of manually hunting across modules.
- Maintain good habits through Daily, Weekly, and Monthly Review flows.
- Predict forgotten tasks: expiring documents, unpaid bills, stale health checks, recurring maintenance.
- Search across every module so the user never has to remember where something is stored.
- Build a household Timeline from important events across modules.
- Track major Life Events, such as moving house, buying a car, having a child, mortgage start, renovations, and energy upgrades.
- Connect domains: a warranty document belongs to a device, a medical PDF belongs to a health result, a recurring bill belongs to finance and reminders.
- Work locally first, with explicit backup/export and optional integrations.
- Support a future mobile app through stable household-aware APIs.
- Use small AI-assisted improvements throughout the product where they reduce effort: categorization, duplicate detection, document tagging, lab summaries, reminder suggestions, and file classification. AI suggestions must always remain reviewable by the user and must never make irreversible decisions automatically.

## 2a. Core Product Principles

### Dashboard as Decision Center

The Dashboard should answer one question: "What should I do today?"

It should prioritize actions over information. Prefer actionable items such as:

- Pay electricity bill.
- Replace heat pump filter.
- Review imported bank transactions.
- Recheck LDL.
- Renew passport in 23 days.
- Review warranty expiring next month.
- Submit overdue water meter reading.

Raw lists and passive charts belong deeper in modules. The Dashboard should surface the next best actions, grouped by urgency and confidence.

### Inbox as Central Review Queue

Inbox should become the single place to review incoming or uncertain information:

- imported bank transactions,
- OCR results,
- parsed laboratory results,
- uploaded documents needing metadata,
- AI suggestions,
- duplicate detection,
- uncategorized expenses,
- failed imports,
- low-confidence classifications.

The Inbox should reduce manual work by turning "find what needs cleanup" into one repeatable review habit. Domain modules may still have local review screens, but the global Inbox should aggregate them.

### Review Workflows

Home OS should encourage regular reviews:

- Daily Review: today tasks, overdue reminders, incoming items.
- Weekly Review: spending summary, health changes, home maintenance, expiring documents.
- Monthly Review: budgets, subscriptions, recurring bills, household statistics.

Reviews should be short, action-oriented, and completable. The app should show progress and make it obvious when the household is "reviewed enough."

### Search, Timeline, and Life Events

Global Search is a first-class product capability. It should search expenses, documents, warranties, invoices, blood results, medications, maintenance tasks, reminders, and future modules.

Timeline should show a chronological history of important household events: house purchase, heat pump installation, blood tests, major purchases, warranties, insurance renewals, mortgage payments, and major expenses.

Life Events is a future domain for major real-world changes: moving house, buying a car, having a child, changing jobs, starting a mortgage, installing photovoltaic panels, or major renovations. Other modules should eventually reference these events.

### Success Metrics

Product decisions should be guided by simple usefulness metrics:

- daily active usage,
- weekly active usage,
- monthly active days,
- average time to add an expense,
- average review completion,
- reminder completion rate,
- document retrieval time,
- dashboard actions completed,
- inbox items resolved,
- time from import/upload to reviewed data.

Do not over-instrument early. Start with product definitions, then add lightweight measurement once core flows are stable.

## 3. Development Phases

| Phase | Purpose | User Value | Dependencies | Complexity |
|---|---|---:|---|---|
| 1. Stabilize Foundation | Add tests, roadmap governance, frontend structure, basic DX | High | Current code | Medium |
| 2. Core Daily Usage | Make Dashboard, quick capture, reminders, home tasks useful daily | Very High | Foundation | Medium |
| 3. Inbox + Review Foundation | Aggregate review queues and create daily/weekly/monthly review habits | Very High | Current Dashboard + Expenses/Health reviews | Medium |
| 4. Home Maintenance | Track recurring maintenance, warranties, inspections, household routines | Very High | Reminders | Medium |
| 5. Documents MVP | Generic document metadata, expiry dates, tags, linked files | Very High | Attachment/storage | Medium |
| 6. Search + Timeline Foundation | Make records findable and build household history | Very High | Stable domain read models | Medium/High |
| 7. Finance Automation | Bank import UI, saved rule auto-apply, category learning, monthly close | Very High | Current Expenses + Inbox | Medium/High |
| 8. Health Intelligence | Health review queue, marker cleanup, stale checks, appointments, medications | High | Current Health + Inbox | Medium/High |
| 9. Security & Reliability | Audit logs, encrypted files, backup/export, permissions later if needed | Very High | Stable domains | High |
| 10. Integrations + AI Assistance | OCR, Home Assistant, calendar, AI assistant, predictive reminders | High later | Stable core data + reviewable suggestions | High |

## 4. Prioritized Backlog

| Priority | Item | Why It Matters | Effort | Impact |
|---|---|---|---:|---:|
| P0 | Add `docs/development-roadmap.md` and update `AGENTS.md` | Makes roadmap the single source of truth | S | High |
| P0 | Add backend API tests for Dashboard, Expenses review, Health import | Protects sensitive core flows | M | Very High |
| P0 | Split frontend into feature folders without changing behavior | Makes future work faster and safer | M | High |
| P0 | Define Dashboard Decision Center model | Keeps Dashboard focused on actions, not passive data | S/M | Very High |
| P0 | Home Maintenance MVP | Fills biggest MVP gap and makes Dashboard useful beyond finance/health | M | Very High |
| P0 | Generic Reminder model | Enables home tasks, document expiry, health follow-ups, bill reminders | M | Very High |
| P0 | Inbox MVP for imported/review-needed items | Creates one place to process uncertainty and reduce manual work | M | Very High |
| P1 | Generic Documents MVP | Organizes contracts, invoices, warranties, manuals, IDs | M | Very High |
| P1 | Dashboard signals for home tasks and document expiry | Turns Dashboard into true daily start page | S/M | Very High |
| P1 | Daily/Weekly/Monthly Review workflows | Builds the habit loop and reduces forgotten work | M | Very High |
| P1 | Health Review Center | Reduces stress around imported/unknown/out-of-range markers | M | High |
| P1 | Global Search MVP | Removes need to remember which module owns a record | M/H | Very High |
| P1 | Mobile-first navigation and quick actions | Makes daily capture realistic | M | High |
| P2 | Timeline MVP | Creates household history from important records | M | High |
| P2 | Email reminder digest | Prevents forgotten tasks without app checking | M | High |
| P3 | Life Events domain | Connects major household changes across modules | M/H | Medium/High |
| P3 | Calendar integration | Useful after reminders/tasks are stable | H | Medium/High |
| P3 | Home Assistant status integration | Valuable later, not before home/domain basics | H | Medium |
| P3 | AI assistant | Powerful after reviewable suggestion flows mature | H | High later |

## 5. Milestones

### Milestone 1: Roadmap + Safety Net

- Create `docs/development-roadmap.md`.
- Link it from `README.md` and `AGENTS.md`.
- Add backend test setup and first tests for login, dashboard, expense overview, health overview.
- Acceptance: new work has a known roadmap location and critical APIs have at least smoke coverage.

### Milestone 2: Home Maintenance MVP

Status: completed.

- Add Home domain with maintenance tasks.
- API: list/create/update/delete/mark done.
- Fields: title, area, due date, recurrence, assigned member, notes, status.
- Dashboard: show overdue/upcoming home tasks.
- Frontend: real `#home` page or replace placeholder with Home section.
- Acceptance: user can track recurring home tasks and see due work on Dashboard.

### Milestone 3: Inbox + Reviews MVP

Status: completed for the smallest useful version.

- Add a global Inbox page/section that aggregates existing review queues from Expenses and Health first.
- Inbox items should have source module, severity/confidence, title, detail, target action, and reviewed/dismissed state where supported.
- Add Daily Review using Dashboard and Inbox: overdue reminders, today actions, imported/review-needed items.
- Keep Weekly and Monthly Review as lightweight summaries until more domains exist.
- Acceptance: user can open one place to process incoming/uncertain information.

### Milestone 4: Generic Reminders

Status: completed for the smallest useful version. Home Maintenance remains independent for now; optional Home-to-Reminder unification can happen later when it is clearly useful.

- Add household reminders with due date, optional recurrence, related type/id, completed/skipped status.
- Keep Home Maintenance independent for now; use reminder links for Home later only if it reduces duplication.
- Dashboard attention: overdue today/upcoming.
- Acceptance: reminders become reusable infrastructure without overbuilding notifications yet.

### Milestone 5: Documents MVP

Status: completed for the smallest useful version. Generic documents are independent from Health documents for now; shared attachment infrastructure can be introduced later if more domains need it.

- Add generic documents domain separate from health documents.
- Metadata: title, type, owner member, issue date, expiry date, tags, note, file.
- Dashboard attention: expiring/expired documents.
- Acceptance: contracts, warranties, invoices, manuals can be stored and found.

### Milestone 6: Search + Timeline Foundation

Status: completed for the smallest useful version. Search and Timeline are read-only query layers assembled from existing records; no indexing table, search engine, or event-sourcing model was introduced.

- Add Global Search MVP across Expenses, Health, Documents, Home tasks, and Reminders as those domains exist.
- Add Timeline read model for important household events generated from domain records.
- Start with deterministic timeline events; avoid a complex event-sourcing abstraction.
- Acceptance: user can find records without knowing the module and can see chronological household history.

### Milestone 7: Health Review Center

Status: completed for the smallest useful version. Review items are deterministic and source-data-based; no diagnosis, AI advice, acknowledgement workflow, or persisted marker mapping was introduced.

- Add a dedicated Health review section for unknown markers, suspicious references, out-of-range results, stale markers.
- Add fast cleanup/edit actions.
- Route health review items into global Inbox.
- Acceptance: imported lab data can be trusted without scanning raw tables.

### Milestone 8: Finance Import Productization

- Status: completed for the smallest useful version. CSV import has preview and accept steps, deterministic duplicate detection across expenses/income, clear skipped/imported/review counts, saved review rule auto-apply, useful parse errors with no partial writes, and imported uncertain rows flow into Inbox and Dashboard review attention. Deduplication remains indexed application-level because imported money records currently live in separate expense/income tables; a shared database uniqueness model can be introduced later if imports become a dedicated ledger.
- Add import UI for bank file upload/preview.
- Auto-apply saved finance rules.
- Review only uncertain transactions.
- Route uncertain transactions and failed imports into global Inbox.
- Acceptance: finance import no longer requires manual database/dev work.

### Milestone 8.5: Daily UX Stabilization

Status: completed for the smallest useful version. This milestone intentionally stayed inside existing domains and focused on daily usability before Mobile Daily Use.

- Split the first frontend surfaces out of `App.tsx`: Dashboard, Inbox, Search, Timeline, and Quick Actions now live in feature folders with feature styles.
- Reorganize Dashboard as a Decision Center with Today, Quick Capture, Review, and Recent Activity.
- Add a persistent global quick action menu for Expense, Reminder, Document, Home Task, and Health Result.
- Improve Inbox processing with source and severity filters.
- Replace passive empty states with setup guidance for Home, Reminders, Documents, Search, and Timeline.
- Reduce Timeline noise by grouping ordinary daily spending while keeping large/review-needed expenses visible.
- Add lightweight frontend smoke checks for Dashboard, Inbox, Expenses Import, Search, Timeline, and Quick Actions.
- Acceptance: Home OS feels more like a daily-use app and is ready to proceed into Mobile Daily Use.

### Milestone 9: Mobile Daily Use

Status: completed for the smallest useful version. This milestone focused on daily web usage from a phone, not native app/PWA behavior.

- Add mobile-friendly navigation and quick action layout.
- Prioritize Dashboard, quick expense, reminders, health review.
- Improve phone form spacing and sticky save/action placement for daily capture flows.
- Verify Dashboard, Inbox, Expenses, Home Tasks, Reminders, Health Review, and Quick Actions at a phone viewport.
- Add mobile-focused frontend smoke checks.
- Acceptance: user can complete daily capture/review comfortably on phone.

### Milestone 10: Reliability & Privacy

Status: completed for the smallest useful version. Attachment export is intentionally metadata-only for now; files remain stored outside the public web root and can still be downloaded through protected endpoints.

- Add audit logs for sensitive edits/deletes.
- Add backup/export.
- Harden uploaded file storage.
- Add `make check` for local verification; CI service wiring remains future work.
- Acceptance: app is safer to trust with real personal data.

## 6. Technical Improvements

### Architecture

- Keep backend DDD/CQRS, but avoid introducing generic abstractions until two domains need the same behavior.
- Introduce Shared `Reminder` only when Home/Documents need it.
- Introduce global Inbox as a thin aggregation/read workflow first, not a heavy cross-domain write model.
- Introduce Timeline as a deterministic read model first, not event sourcing.
- Introduce Shared `Attachment` when Documents becomes generic; health documents can migrate later.
- Keep session auth for local web, design APIs so JWT/mobile auth can be added later.

### Frontend

- Split `App.tsx` into `features/dashboard`, `features/expenses`, `features/health`, `features/household`, `features/documents`, `shared/api`, `shared/format`.
- Keep hash routing until feature extraction is done; then consider React Router.
- Do not add TanStack Query until API calls become hard to reason about after extraction.

### Testing

- Backend first: API tests for auth, household access, expenses overview/review, dashboard attention, health import/review.
- Add tests that Dashboard returns action-oriented attention items, not only raw counts.
- Add tests that Inbox aggregates review-needed items from source domains without breaking source workflows.
- Frontend next: build check, lightweight component tests for dashboard actions, Playwright smoke flows later.
- Add fixture builders for household, expenses, health results.

### Security

- Keep files outside public web root.
- Keep file type/size validation and safer download headers covered by tests as upload paths evolve.
- Extend audit coverage only when new sensitive workflows are introduced.
- JSON export exists for source data; restore, encrypted storage, and attachment archive export remain future reliability work.
- Roles remain out of scope until more than one access level is genuinely needed.

### Performance

- Watch dashboard query growth; it currently aggregates Expenses and Health.
- Add targeted indexes when Home/Documents/Reminders add due-date queries.
- Avoid expensive per-marker history loops if health data grows; replace with repository-level latest-marker query later.

### Developer Experience

- Keep `make check` green for backend smoke tests, Symfony container lint, Doctrine schema validation, frontend build, and frontend smoke checks.
- Add `make test` once tests exist.
- Keep AGENTS and roadmap synchronized.

## 7. UX Improvements

### Dashboard

- Make Dashboard the default daily start.
- Evolve Dashboard into a Decision Center answering "What should I do today?"
- Show Today, Upcoming, Overdue, Review Queues, Recent Activity, and next best actions.
- Keep actions one click away: add expense, review imports, mark bill paid, mark task done, review health.
- Prefer actionable cards over raw lists or passive metrics.

### Inbox

- Add one place to review incoming/uncertain data across modules.
- Use consistent actions: approve, edit, dismiss, merge duplicate, retry import, open source.
- Keep domain-specific review screens, but make Inbox the daily entry point.

### Reviews

- Add Daily Review first: today actions, overdue items, incoming review items.
- Add Weekly Review next: spending, health changes, maintenance, expiring documents.
- Keep Monthly Review focused on budgets, subscriptions, recurring bills, and household statistics.

### Navigation

- Add real Home page.
- Add Inbox once it aggregates at least Expenses and Health.
- Add Search as a persistent top-level affordance once at least three modules are searchable.
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
- Small reviewable AI suggestions throughout the app: expense category, duplicate documents, lab summaries, abnormal marker explanations, reminder dates, document tags, and file classification.
- AI lab result summarizer with strict medical disclaimers and source references.
- AI finance categorization suggestions, always reviewable.
- Predictive monthly cashflow warnings.
- Smart reminders based on patterns.
- Global Inbox for AI and import suggestions.
- Global Search across every module.
- Timeline of important household events.
- Life Events domain for moving, buying a car, having a child, changing jobs, mortgage start, photovoltaic panels, and major renovations.
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

- `GET /api/dashboard/actions`
- `GET /api/households/{householdId}/inbox`
- `PATCH /api/households/{householdId}/inbox/{itemId}`
- `GET /api/households/{householdId}/reviews/daily`
- `GET /api/households/{householdId}/reviews/weekly`
- `GET /api/households/{householdId}/reviews/monthly`
- `GET /api/households/{householdId}/search?q=...`
- `GET /api/households/{householdId}/timeline`
- `GET /api/households/{householdId}/life-events`
- `POST /api/households/{householdId}/life-events`
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
- Expand `GET /api/dashboard` summary and attention items to include action-oriented home tasks, reminders, document expiry, Inbox counts, and review progress.

## Test Plan

- Roadmap artifact: verify `docs/development-roadmap.md`, `README.md`, and `AGENTS.md` all point to the same roadmap.
- Backend smoke: auth, household access, dashboard, expense overview, health overview.
- Dashboard Decision Center: returns prioritized actions and navigates to the right source workflows.
- Inbox: aggregates review-needed Expenses and Health items without duplicating source data ownership.
- Review workflows: Daily Review includes today/overdue/imported items; Weekly/Monthly can start as read-only summaries.
- Search: finds seeded records across implemented modules.
- Timeline: displays deterministic events generated from implemented modules.
- Expenses: add/edit/delete expense, import review rule, undo batch, budget warning, overdue bill attention.
- Health: upload document, extract markers, import blood test, out-of-range attention, stale marker attention.
- Future Home: create task, mark done, recurrence creates next due task or computes next due date, dashboard shows overdue/upcoming.
- Documents: upload metadata/file, edit/delete metadata, expiry warning, household access protection.
- Frontend: production build, dashboard actions navigate correctly, mobile viewport layout stays usable.

## Assumptions

- Roadmap language is English to match current docs and UI.
- The canonical roadmap file is `docs/development-roadmap.md`.
- No roles are needed in MVP.
- PLN remains the only currency for now.
- Local-first remains the default; cloud integrations are optional later.
- Email is the first notification channel.
- Home Assistant integration waits until Home/Reminders/Documents are useful without smart home.
- AI can appear earlier as small reviewable suggestions, but deterministic workflows remain the source of truth.
- AI must assist decisions and reduce manual work; it must not make irreversible changes automatically.
- Product metrics should start as definitions in the roadmap, then become lightweight instrumentation after core flows stabilize.

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
