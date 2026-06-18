# Home OS Product Vision

Home OS is a private family management app for daily life. It should help manage the home, money, health records, documents, routines, reminders, and decisions from one secure place.

The first version should feel calm and practical. It should not try to automate everything immediately. It should start by collecting important information cleanly, showing what needs attention, and making repeated tasks easier.

## Core Principles

- Family first: the app is shared by a household, with member-level data where it matters.
- Privacy first: health, finance, and home data are sensitive.
- Modular design: each life area is its own domain, connected by dashboard, reminders, documents, and search.
- Local-first where practical: the app should be able to run at home without depending on a cloud provider.
- Auditability: important records should keep history, timestamps, and source files.
- Simple workflows first: capture, review, remind, report.
- Mobile-ready API: React is first, but future mobile clients should be able to use the same backend.

## Main Modules

- Household: family members, household profile, shared settings.
- Home: property, rooms, maintenance tasks, inventory, meter readings, subscriptions, warranties.
- Expenses: PLN transactions, categories, budgets, recurring bills, receipts, reports.
- Health: profiles, blood test results, measurements, medications, appointments, documents, trends.
- Documents: invoices, medical PDFs, contracts, manuals, warranties, tags, expiry dates.
- Tasks and reminders: recurring jobs, due dates, notifications, review queues.
- Dashboard: today, upcoming, warnings, spending snapshot, latest health changes.

## First Useful Version

The MVP should support:

- Login with email and password.
- Household and family members.
- Dashboard with upcoming reminders and recent records.
- Manual PLN expense entry with categories and paid-by member.
- General health records and blood test result entry with units and reference ranges.
- Document upload metadata.
- Home maintenance reminders.

Home device control can come after the foundation is stable. The future target integration is Home Assistant running on the user's own server.
