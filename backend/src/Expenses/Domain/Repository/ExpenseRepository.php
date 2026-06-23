<?php

namespace App\Expenses\Domain\Repository;

use App\Expenses\Domain\Model\Expense;
use App\Expenses\Domain\Model\ExpenseBudget;
use App\Expenses\Domain\Model\ExpenseCategory;
use App\Expenses\Domain\Model\IncomeEntry;
use App\Expenses\Domain\Model\IncomeSource;
use App\Expenses\Domain\Model\RecurringBill;
use App\Expenses\Domain\Model\RecurringBillPayment;

interface ExpenseRepository
{
    public function saveCategory(ExpenseCategory $category): void;

    public function saveExpense(Expense $expense): void;

    public function saveRecurringBill(RecurringBill $bill): void;

    public function saveIncomeSource(IncomeSource $source): void;

    public function saveIncomeEntry(IncomeEntry $entry): void;

    public function saveBudget(ExpenseBudget $budget): void;

    public function saveRecurringBillPayment(RecurringBillPayment $payment): void;

    public function getCategory(string $householdId, string $categoryId): ExpenseCategory;

    public function getExpense(string $householdId, string $expenseId): Expense;

    public function getRecurringBill(string $householdId, string $billId): RecurringBill;

    public function getIncomeSource(string $householdId, string $sourceId): IncomeSource;

    public function getIncomeEntry(string $householdId, string $entryId): IncomeEntry;

    public function findBudget(string $householdId, string $categoryId, string $month): ?ExpenseBudget;

    public function findRecurringBillPayment(string $householdId, string $billId, string $month): ?RecurringBillPayment;

    /**
     * @return list<ExpenseCategory>
     */
    public function categoriesForHousehold(string $householdId): array;

    /**
     * @return list<Expense>
     */
    public function latestExpenses(string $householdId, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null, ?string $categoryId = null, ?string $paidByMemberId = null, int $limit = 20): array;

    /**
     * @return list<Expense>
     */
    public function expensesBetween(string $householdId, \DateTimeImmutable $from, \DateTimeImmutable $to, ?string $categoryId = null, ?string $paidByMemberId = null): array;

    /**
     * @return list<RecurringBill>
     */
    public function recurringBillsForHousehold(string $householdId, ?string $categoryId = null, ?string $paidByMemberId = null): array;

    /**
     * @return list<IncomeSource>
     */
    public function incomeSourcesForHousehold(string $householdId): array;

    /**
     * @return list<IncomeEntry>
     */
    public function incomeEntriesBetween(string $householdId, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    /**
     * @return list<ExpenseBudget>
     */
    public function budgetsForMonth(string $householdId, string $month): array;

    /**
     * @return list<RecurringBillPayment>
     */
    public function recurringBillPaymentsForMonth(string $householdId, string $month): array;
}
