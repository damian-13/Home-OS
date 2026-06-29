<?php

namespace App\Expenses\Application\Import;

use App\Expenses\Application\DefaultExpenseCategories;
use App\Expenses\Domain\Model\Expense;
use App\Expenses\Domain\Model\ExpenseCategory;
use App\Expenses\Domain\Model\FinanceReviewRule;
use App\Expenses\Domain\Model\IncomeEntry;
use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Domain\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;
use SplFileObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class FinanceImportService
{
    public function __construct(private ExpenseRepository $expenses)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(string $householdId, UploadedFile $file, string $importSource): array
    {
        $rows = $this->normalizedRows($file, $importSource);
        $rules = $this->expenses->reviewRulesForHousehold($householdId);
        $previewRows = [];
        $duplicates = 0;
        $newRows = 0;
        $expenseRows = 0;
        $incomeRows = 0;
        $rowsNeedingReview = 0;
        $autoReviewedRows = 0;
        $matchedRuleRows = 0;

        foreach ($rows as $normalized) {
            $duplicate = $this->expenses->findImportedTransaction($householdId, $normalized['importSource'], $normalized['fingerprint']);
            $matchedRule = $duplicate ? null : $this->matchRule($rules, $normalized);

            if ($duplicate) {
                ++$duplicates;
            } else {
                ++$newRows;
                if ($matchedRule) {
                    ++$matchedRuleRows;
                    ++$autoReviewedRows;
                } else {
                    ++$rowsNeedingReview;
                }
            }

            if ($normalized['direction'] === 'expense') {
                ++$expenseRows;
            } else {
                ++$incomeRows;
            }

            $previewRows[] = $this->previewRow($normalized, $duplicate, $matchedRule);
        }

        return [
            'summary' => [
                'totalRows' => count($previewRows),
                'newRows' => $newRows,
                'duplicateCandidates' => $duplicates,
                'autoSkippedDuplicates' => $duplicates,
                'rowsNeedingReview' => $rowsNeedingReview,
                'autoReviewedRows' => $autoReviewedRows,
                'matchedRuleRows' => $matchedRuleRows,
                'incomeRows' => $incomeRows,
                'expenseRows' => $expenseRows,
            ],
            'rows' => $previewRows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function accept(string $householdId, UploadedFile $file, string $importSource): array
    {
        $rows = $this->normalizedRows($file, $importSource);
        $rules = $this->expenses->reviewRulesForHousehold($householdId);
        $category = $this->ensureFallbackCategory($householdId);
        $created = [];
        $skipped = [];
        $createdExpenses = 0;
        $createdIncomeEntries = 0;
        $rowsStillNeedingReview = 0;
        $autoReviewedRows = 0;
        $matchedRuleRows = 0;

        foreach ($rows as $normalized) {
            $duplicate = $this->expenses->findImportedTransaction($householdId, $normalized['importSource'], $normalized['fingerprint']);

            if ($duplicate) {
                $skipped[] = $this->previewRow($normalized, $duplicate, null);
                continue;
            }

            $matchedRule = $this->matchRule($rules, $normalized);
            if ($normalized['direction'] === 'expense') {
                $categoryForExpense = $category;
                if ($matchedRule instanceof FinanceReviewRule && $matchedRule->categoryId()) {
                    $categoryForExpense = $this->expenses->getCategory($householdId, $matchedRule->categoryId());
                }

                $expense = new Expense(
                    (string) Uuid::new(),
                    $householdId,
                    $categoryForExpense,
                    $normalized['description'],
                    $normalized['amountCents'],
                    $normalized['currency'],
                    $normalized['transactionDate'],
                    null,
                    $normalized['importSource'],
                    $normalized['fingerprint'],
                );
                if ($matchedRule instanceof FinanceReviewRule) {
                    $expense->changeReview('reviewed');
                    $matchedRule->markApplied();
                    $this->expenses->saveReviewRule($matchedRule);
                    ++$matchedRuleRows;
                    ++$autoReviewedRows;
                } else {
                    $expense->changeReview('needs_review', 'Imported bank transaction needs category check');
                    ++$rowsStillNeedingReview;
                }
                $this->expenses->saveExpense($expense);
                ++$createdExpenses;
                $created[] = ['type' => 'expense', 'id' => $expense->id(), 'reviewStatus' => $expense->reviewStatus(), 'matchedRule' => $this->ruleView($matchedRule)];
                continue;
            }

            $entry = new IncomeEntry(
                (string) Uuid::new(),
                $householdId,
                null,
                null,
                $normalized['description'],
                $normalized['amountCents'],
                $normalized['currency'],
                $normalized['transactionDate'],
                $normalized['importSource'],
                $normalized['fingerprint'],
            );
            if ($matchedRule instanceof FinanceReviewRule && $matchedRule->incomeKind()) {
                $entry->changeClassification($matchedRule->incomeKind(), 'reviewed');
                $matchedRule->markApplied();
                $this->expenses->saveReviewRule($matchedRule);
                ++$matchedRuleRows;
                ++$autoReviewedRows;
            } else {
                $entry->changeClassification('other', 'needs_review', 'Imported bank transaction needs income type check');
                ++$rowsStillNeedingReview;
            }
            $this->expenses->saveIncomeEntry($entry);
            ++$createdIncomeEntries;
            $created[] = ['type' => 'income', 'id' => $entry->id(), 'reviewStatus' => $entry->reviewStatus(), 'matchedRule' => $this->ruleView($matchedRule)];
        }

        return [
            'summary' => [
                'totalRows' => count($rows),
                'createdRows' => count($created),
                'createdExpenses' => $createdExpenses,
                'createdIncomeEntries' => $createdIncomeEntries,
                'skippedDuplicates' => count($skipped),
                'rowsStillNeedingReview' => $rowsStillNeedingReview,
                'autoReviewedRows' => $autoReviewedRows,
                'matchedRuleRows' => $matchedRuleRows,
            ],
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    private function parseFile(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'txt'], true)) {
            throw new InvalidArgumentException('Finance import currently supports CSV files only.');
        }

        $path = (string) $file->getRealPath();
        $separator = $this->detectSeparator($path);
        $handle = new SplFileObject($path);
        $handle->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $handle->setCsvControl($separator);

        $headers = null;
        $rows = [];

        foreach ($handle as $line) {
            if (!is_array($line) || $line === [null]) {
                continue;
            }

            $line = array_map(static fn ($value): string => trim((string) $value), $line);

            if ($headers === null) {
                $headers = array_map(fn (string $header): string => $this->normalizeHeader($header), $line);
                continue;
            }

            $row = [];
            foreach ($headers as $column => $header) {
                if ($header === '') {
                    continue;
                }
                $row[$header] = $line[$column] ?? '';
            }

            if (array_filter($row, static fn (string $value): bool => $value !== '') !== []) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizedRows(UploadedFile $file, string $importSource): array
    {
        $rows = $this->parseFile($file);

        if ($rows === []) {
            throw new InvalidArgumentException('Import file does not contain any transaction rows.');
        }

        return array_map(
            fn (array $row, int $index): array => $this->normalizeRow($row, $importSource, $index + 1),
            $rows,
            array_keys($rows),
        );
    }

    private function detectSeparator(string $path): string
    {
        $sample = (string) file_get_contents($path, false, null, 0, 4096);
        $semicolons = substr_count($sample, ';');
        $commas = substr_count($sample, ',');

        return $semicolons > $commas ? ';' : ',';
    }

    private function normalizeHeader(string $header): string
    {
        $normalized = strtolower(trim($header));
        $normalized = strtr($normalized, [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
            'ó' => 'o', 'ś' => 's', 'ż' => 'z', 'ź' => 'z',
        ]);
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? $normalized;

        return trim($normalized, '_');
    }

    /**
     * @param array<string, string> $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row, string $importSource, int $rowNumber): array
    {
        $source = $this->normalizeText($importSource !== '' ? $importSource : 'bank-csv');
        $transactionId = $this->firstValue($row, ['transaction_id', 'bank_transaction_id', 'id_transakcji', 'identyfikator_transakcji', 'nr_transakcji']);
        $account = $this->firstValue($row, ['account', 'bank_account', 'rachunek', 'konto', 'numer_rachunku']);
        $transactionDateValue = $this->firstValue($row, ['transaction_date', 'date', 'spent_on', 'received_on', 'data_transakcji', 'data_operacji', 'data']);
        $bookingDateValue = $this->firstValue($row, ['booking_date', 'booked_at', 'accounting_date', 'data_ksiegowania', 'data_waluty']);
        $amountValue = $this->firstValue($row, ['amount', 'kwota', 'transaction_amount', 'wartosc']);
        if ($amountValue === '') {
            throw new InvalidArgumentException(sprintf('Transaction amount is required in import row %d.', $rowNumber));
        }

        $amount = $this->parseAmount($amountValue);
        $currency = strtoupper($this->firstValue($row, ['currency', 'waluta']) ?: 'PLN');
        $merchant = $this->firstValue($row, ['merchant', 'counterparty', 'contractor', 'odbiorca', 'nadawca', 'kontrahent']);
        $title = $this->firstValue($row, ['title', 'description', 'tytul', 'opis', 'details', 'szczegoly']);
        $description = trim($merchant !== '' && $title !== '' ? $merchant.' '.$title : ($merchant ?: $title));

        if ($transactionDateValue === '') {
            throw new InvalidArgumentException(sprintf('Transaction date is required in import row %d.', $rowNumber));
        }

        if ($amount === 0) {
            throw new InvalidArgumentException(sprintf('Transaction amount is required in import row %d.', $rowNumber));
        }

        if ($description === '') {
            $description = sprintf('Imported transaction %d', $rowNumber);
        }

        $transactionDate = $this->parseDate($transactionDateValue, $rowNumber);
        $bookingDate = $bookingDateValue !== '' ? $this->parseDate($bookingDateValue, $rowNumber) : null;
        $amountCents = (int) round(abs($amount) * 100);
        $direction = $amount < 0 ? 'expense' : 'income';

        $fingerprintBasis = $transactionId !== ''
            ? ['stable-id', $source, $this->normalizeText($transactionId)]
            : [
                'normalized-row',
                $source,
                $this->normalizeText($account),
                $transactionDate->format('Y-m-d'),
                $bookingDate?->format('Y-m-d') ?? '',
                (string) ((int) round($amount * 100)),
                $currency,
                $this->normalizeText($merchant),
                $this->normalizeText($title),
            ];

        return [
            'rowNumber' => $rowNumber,
            'direction' => $direction,
            'importSource' => $source,
            'fingerprint' => hash('sha256', implode('|', $fingerprintBasis)),
            'fingerprintStrength' => $transactionId !== '' ? 'stable_transaction_id' : 'normalized_transaction_data',
            'transactionDate' => $transactionDate,
            'bookingDate' => $bookingDate,
            'amountCents' => $amountCents,
            'amount' => $amountCents / 100,
            'signedAmount' => $amount,
            'currency' => $currency,
            'merchant' => $merchant,
            'description' => mb_substr($description, 0, 120),
        ];
    }

    /**
     * @param array<string, string> $row
     * @param list<string> $keys
     */
    private function firstValue(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && trim($row[$key]) !== '') {
                return trim($row[$key]);
            }
        }

        return '';
    }

    private function parseAmount(string $value): float
    {
        $normalized = trim($value);
        $normalized = str_replace(["\xc2\xa0", ' ', 'PLN', 'pln'], '', $normalized);

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '.', $normalized);
        }

        return (float) $normalized;
    }

    private function parseDate(string $value, int $rowNumber): DateTimeImmutable
    {
        $normalized = trim($value);
        $formats = ['Y-m-d', 'd.m.Y', 'd-m-Y', 'Y/m/d', 'd/m/Y'];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $normalized);
            if ($date instanceof DateTimeImmutable) {
                return $date;
            }
        }

        try {
            return new DateTimeImmutable($normalized);
        } catch (\Throwable) {
            throw new InvalidArgumentException(sprintf('Invalid transaction date in import row %d.', $rowNumber));
        }
    }

    private function normalizeText(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return $normalized;
    }

    /**
     * @param array<string, mixed> $normalized
     * @param array{type: 'expense'|'income', id: string}|null $duplicate
     * @return array<string, mixed>
     */
    private function previewRow(array $normalized, ?array $duplicate, ?FinanceReviewRule $matchedRule): array
    {
        return [
            'rowNumber' => $normalized['rowNumber'],
            'direction' => $normalized['direction'],
            'status' => $duplicate ? 'duplicate_candidate' : 'new',
            'duplicate' => $duplicate !== null,
            'confidence' => $duplicate ? 'high' : null,
            'recommendedAction' => $duplicate ? 'skip' : ($matchedRule ? 'auto_review' : 'review'),
            'matchedRecord' => $duplicate,
            'matchedRule' => $this->ruleView($matchedRule),
            'description' => $normalized['description'],
            'amount' => $normalized['amount'],
            'currency' => $normalized['currency'],
            'transactionDate' => $normalized['transactionDate']->format('Y-m-d'),
            'bookingDate' => $normalized['bookingDate']?->format('Y-m-d'),
            'importSource' => $normalized['importSource'],
            'fingerprintStrength' => $normalized['fingerprintStrength'],
        ];
    }

    /**
     * @param list<FinanceReviewRule> $rules
     * @param array<string, mixed> $normalized
     */
    private function matchRule(array $rules, array $normalized): ?FinanceReviewRule
    {
        foreach ($rules as $rule) {
            if (!$rule->active() || $rule->targetType() !== $normalized['direction']) {
                continue;
            }

            if (mb_stripos((string) $normalized['description'], $rule->matchText()) !== false) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * @return array{id: string, targetType: string, matchText: string, categoryId: ?string, incomeKind: ?string}|null
     */
    private function ruleView(?FinanceReviewRule $rule): ?array
    {
        if (!$rule instanceof FinanceReviewRule) {
            return null;
        }

        return [
            'id' => $rule->id(),
            'targetType' => $rule->targetType(),
            'matchText' => $rule->matchText(),
            'categoryId' => $rule->categoryId(),
            'incomeKind' => $rule->incomeKind(),
        ];
    }

    private function ensureFallbackCategory(string $householdId): ExpenseCategory
    {
        $categories = $this->expenses->categoriesForHousehold($householdId);

        if ($categories === []) {
            foreach (DefaultExpenseCategories::all() as $defaultCategory) {
                $this->expenses->saveCategory(new ExpenseCategory(
                    (string) Uuid::new(),
                    $householdId,
                    $defaultCategory['name'],
                    $defaultCategory['slug'],
                    $defaultCategory['color'],
                ));
            }

            $categories = $this->expenses->categoriesForHousehold($householdId);
        }

        foreach ($categories as $category) {
            if ($category->slug() === 'other') {
                return $category;
            }
        }

        if ($categories === []) {
            throw new InvalidArgumentException('Could not prepare an import category.');
        }

        return $categories[0];
    }
}
