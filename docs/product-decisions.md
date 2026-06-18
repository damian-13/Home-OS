# Product Decisions

## Name

Home OS

## Audience

Home OS is a private family app. It starts with adults and should support children later.

## Access Model

- Multiple family members belong to one household.
- No roles in the MVP.
- Authentication uses email and password.
- The backend should not assume there will never be roles; it should simply avoid building role complexity now.

## Language

- The app should support Polish and English.
- Frontend routes and first UI implementation use English.
- Domain labels such as expense categories should be stored in a way that can be translated.

## Currency

- PLN is the main currency.
- Money should be modeled as a value object with amount and currency, even if PLN is the only currency at first.

## Storage

- Documents are stored locally for now.
- Files must not be placed in the public web root.
- The design should allow encrypted storage and backups later.

## Smart Home

- Smart home control is not part of the first MVP.
- Future integration should target Home Assistant running on the user's own server.
- The domain model should reserve space for external device references, but no device control should be built until the core family data modules are stable.

## Notifications

- Email is enough for the first notification channel.
- Notifications are not critical for the first build, but reminders should be modeled from the beginning.

## Architecture

- Backend uses Symfony with DDD and CQRS.
- React frontend talks to Symfony through HTTP APIs.
- The API should be designed so a mobile app can use it in the future.
- One PostgreSQL database is enough for MVP write models and read models.
