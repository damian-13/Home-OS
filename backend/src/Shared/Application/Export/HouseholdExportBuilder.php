<?php

namespace App\Shared\Application\Export;

use Doctrine\DBAL\Connection;

final readonly class HouseholdExportBuilder
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(string $householdId): array
    {
        return [
            'format' => 'home-os-household-export-v1',
            'exportedAt' => gmdate(DATE_ATOM),
            'attachmentsIncluded' => false,
            'attachmentNote' => 'File attachments are stored outside the public web root and are not embedded in this JSON export yet. Document metadata includes original names and download URLs.',
            'household' => $this->one('SELECT id, name, default_currency, created_at FROM households WHERE id = :householdId', $householdId),
            'members' => $this->many('SELECT id, display_name, member_type, birth_date, color, active, created_at FROM household_members WHERE household_id = :householdId ORDER BY created_at ASC', $householdId),
            'expenses' => [
                'categories' => $this->many('SELECT id, name, slug, color FROM expense_categories WHERE household_id = :householdId ORDER BY name ASC', $householdId),
                'items' => $this->many('SELECT id, category_id, description, amount_cents, currency, spent_on, paid_by_member_id, created_at, deleted_at, review_status, review_reason, import_source, import_fingerprint FROM expenses WHERE household_id = :householdId ORDER BY spent_on DESC, created_at DESC', $householdId),
                'incomeSources' => $this->many('SELECT id, member_id, name, amount_cents, currency, active, created_at, deleted_at FROM income_sources WHERE household_id = :householdId ORDER BY created_at DESC', $householdId),
                'incomeEntries' => $this->many('SELECT id, source_id, member_id, description, amount_cents, currency, received_on, income_kind, review_status, review_reason, import_source, import_fingerprint, created_at, deleted_at FROM income_entries WHERE household_id = :householdId ORDER BY received_on DESC, created_at DESC', $householdId),
                'budgets' => $this->many('SELECT id, category_id, budget_month, amount_cents, created_at FROM expense_budgets WHERE household_id = :householdId ORDER BY budget_month DESC', $householdId),
                'recurringBills' => $this->many('SELECT id, category_id, name, amount_cents, currency, due_day, paid_by_member_id, active, created_at, deleted_at FROM recurring_bills WHERE household_id = :householdId ORDER BY due_day ASC', $householdId),
                'recurringBillPayments' => $this->many('SELECT id, recurring_bill_id, payment_month, status, paid_on, amount_override_cents, created_at FROM recurring_bill_payments WHERE household_id = :householdId ORDER BY payment_month DESC', $householdId),
            ],
            'health' => [
                'bloodTests' => $this->many('SELECT id, member_id, tested_at, lab_name, notes, source_document_id, created_at, deleted_at FROM blood_tests WHERE household_id = :householdId ORDER BY tested_at DESC', $householdId),
                'bloodTestMarkers' => $this->many('SELECT marker.id, marker.blood_test_id, marker.marker_name, marker.value, marker.unit, marker.reference_min, marker.reference_max, marker.status, marker.notes FROM blood_test_markers marker INNER JOIN blood_tests test ON test.id = marker.blood_test_id WHERE test.household_id = :householdId ORDER BY test.tested_at DESC, marker.marker_name ASC', $householdId),
                'documents' => $this->many('SELECT id, member_id, document_type, original_name, mime_type, size, uploaded_at FROM health_documents WHERE household_id = :householdId ORDER BY uploaded_at DESC', $householdId),
            ],
            'homeMaintenanceTasks' => $this->many('SELECT id, title, area, next_due_at, recurrence_type, assigned_member_id, priority, notes, status, created_at, completed_at, deleted_at FROM home_maintenance_tasks WHERE household_id = :householdId ORDER BY next_due_at ASC', $householdId),
            'reminders' => $this->many('SELECT id, title, note, due_at, recurrence_type, related_type, related_id, status, priority, created_at, completed_at, skipped_at, deleted_at FROM reminders WHERE household_id = :householdId ORDER BY due_at ASC', $householdId),
            'documents' => $this->many('SELECT id, title, document_type, owner_member_id, issued_at, expires_at, tags, note, original_name, mime_type, file_size, created_at, updated_at, deleted_at FROM documents WHERE household_id = :householdId ORDER BY created_at DESC', $householdId),
            'auditLogs' => $this->many('SELECT id, actor_user_id, entity_type, entity_id, action, changed_at, summary, metadata FROM audit_logs WHERE household_id = :householdId ORDER BY changed_at DESC LIMIT 500', $householdId),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function one(string $sql, string $householdId): ?array
    {
        $row = $this->connection->fetchAssociative($sql, ['householdId' => $householdId]);

        return $row === false ? null : $this->normalizeRow($row);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function many(string $sql, string $householdId): array
    {
        return array_map(
            fn (array $row): array => $this->normalizeRow($row),
            $this->connection->fetchAllAssociative($sql, ['householdId' => $householdId]),
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_resource($value)) {
                $row[$key] = stream_get_contents($value);
            }

            if ($key === 'metadata' && is_string($row[$key])) {
                $decoded = json_decode($row[$key], true);
                $row[$key] = json_last_error() === JSON_ERROR_NONE ? $decoded : $row[$key];
            }
        }

        return $row;
    }
}
