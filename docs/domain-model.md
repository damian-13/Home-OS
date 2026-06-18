# Initial Domain Model

This model is intentionally larger than the MVP so the first implementation does not paint the app into a corner. The MVP should still build only the smallest useful slice.

## Shared Kernel

### HouseholdId, MemberId, UserId

Typed identifiers used across contexts.

### Money

- amount
- currency

Currency starts as PLN, but the value object should still carry currency explicitly.

### LocalizedName

- key
- polishName
- englishName

Used for categories and labels that need Polish and English support.

### Attachment

- id
- householdId
- originalFilename
- mimeType
- storagePath
- sizeBytes
- checksum
- uploadedBy
- uploadedAt

Files are stored locally and outside the public web root.

### Reminder

- id
- householdId
- title
- dueAt
- recurrenceRule
- relatedType
- relatedId
- completedAt
- createdAt

## Identity Context

### UserAccount

- id
- householdId
- email
- passwordHash
- displayName
- linkedMemberId
- createdAt
- lastLoginAt

## Household Context

### Household

- id
- name
- defaultCurrency
- createdAt

### HouseholdMember

- id
- householdId
- displayName
- memberType
- birthDate
- color
- active
- createdAt

Member type starts with adult and child. Children can be added later.

## Finance Context

### Expense

- id
- householdId
- amount
- currency
- categoryId
- merchant
- spentAt
- paidByMemberId
- note
- receiptAttachmentId
- createdBy
- createdAt

### ExpenseCategory

- id
- householdId
- key
- polishName
- englishName
- color
- active

Initial categories:

- Rachunki
- Zakupy spozywcze
- Zakupy domowe
- Rata kredytu
- Telefon i internet
- Transport
- Zdrowie
- Ubezpieczenia
- Subskrypcje
- Edukacja
- Dzieci
- Rozrywka
- Inne

### Budget

- id
- householdId
- categoryId
- monthlyLimit
- currency
- activeFrom
- activeTo

### RecurringExpense

- id
- householdId
- title
- amount
- currency
- categoryId
- recurrenceRule
- nextDueAt
- paidByMemberId
- active

## Health Context

### HealthProfile

- id
- householdId
- memberId
- bloodType
- allergies
- chronicConditions
- emergencyNotes
- updatedAt

### HealthNote

- id
- householdId
- memberId
- title
- note
- recordedAt
- createdBy

### LabMarker

- id
- key
- polishName
- englishName
- commonUnits
- category
- description

Examples: glucose, hemoglobin, ferritin, vitamin D, CRP, TSH, LDL, HDL.

### LabTest

- id
- householdId
- memberId
- testedAt
- labName
- sourceAttachmentId
- note
- createdAt

### LabResult

- id
- labTestId
- markerId
- value
- unit
- referenceLow
- referenceHigh
- flag

### Measurement

- id
- householdId
- memberId
- type
- value
- unit
- measuredAt
- note

Examples: weight, blood pressure, pulse, temperature.

### MedicalAppointment

- id
- householdId
- memberId
- specialist
- location
- scheduledAt
- reason
- notes
- reminderId

### Medication

- id
- householdId
- memberId
- name
- dosage
- schedule
- startDate
- endDate
- note

### Vaccination

- id
- householdId
- memberId
- name
- vaccinatedAt
- validUntil
- attachmentId

## Documents Context

### Document

- id
- householdId
- title
- type
- ownerMemberId
- attachmentId
- issuedAt
- expiresAt
- tags
- note
- createdAt

Initial document types:

- Contract
- Invoice
- Medical
- Warranty
- Insurance
- Tax
- Manual
- Other

### DocumentRelation

- id
- documentId
- relatedType
- relatedId

## Home Context

### Property

- id
- householdId
- name
- address
- note

### Room

- id
- propertyId
- name
- floor

### HomeMaintenanceTask

- id
- householdId
- title
- area
- recurrenceRule
- nextDueAt
- lastCompletedAt
- priority
- note

Examples: boiler service, filter change, smoke detector check, meter reading.

### HomeMaintenanceCompletion

- id
- taskId
- completedByMemberId
- completedAt
- note

### Meter

- id
- householdId
- type
- name
- unit

### MeterReading

- id
- meterId
- value
- readAt
- enteredByMemberId

### HomeInventoryItem

- id
- householdId
- roomId
- name
- category
- purchaseDate
- warrantyUntil
- serialNumber
- documentId

## Integrations Context

### IntegrationConnection

- id
- householdId
- type
- name
- baseUrl
- status
- createdAt

The first planned integration type is Home Assistant.

### ExternalDeviceReference

- id
- connectionId
- externalId
- name
- type
- roomId
- lastSeenAt

No direct device control in MVP.
