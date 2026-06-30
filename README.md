# Home OS

Personal home and life management app built with Symfony backend and React frontend.

The goal is to manage:

- Home maintenance, inventory, documents, and future smart-home control.
- PLN expenses, budgets, bills, receipts, and reports.
- Family health records, blood tests, appointments, and trends.
- Documents, reminders, and a daily dashboard.

## Planned Stack

- Backend: PHP 8.5, Symfony 8.1, PostgreSQL.
- Frontend: React, TypeScript, Vite.
- Development: Docker Compose.

## Current State

This repository contains a Symfony backend, a React frontend, Docker Compose development services, and architecture notes.

## Local Tooling

This machine currently has Docker available, but PHP, Composer, Symfony CLI, npm, pnpm, and yarn were not available on the shell path when the project was initialized. The repo is set up to use Docker for the missing tools.

## Setup Plan

1. Copy environment defaults:

   ```sh
   make setup
   ```

2. Start development:

   ```sh
   make start
   ```

3. Open:

   ```text
   Backend:  http://localhost:8080
   Frontend: http://localhost:5173
   Mailpit:  http://localhost:8025
   ```

## Documentation

- [Agent context](AGENTS.md)
- [Development roadmap](docs/development-roadmap.md)
- [Product vision](docs/product-vision.md)
- [Product decisions](docs/product-decisions.md)
- [Architecture](docs/architecture.md)
- [Notifications](docs/notifications.md)
- [Initial domain model](docs/domain-model.md)
- [MVP roadmap](docs/mvp-roadmap.md)

## First Build Target

The first working slice should be:

- Email/password login.
- Household and family members.
- Dashboard.
- Manual PLN expense entry.
- General health profiles and manual blood test result entry.
- Home maintenance reminder.

That gives the app a useful core before adding smart-home integrations and advanced imports.
