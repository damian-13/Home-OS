<?php

namespace App\Expenses\Domain\Repository;

use App\Expenses\Domain\Model\Expense;
use App\Expenses\Domain\Model\ExpenseCategory;
use App\Expenses\Domain\Model\RecurringBill;

interface ExpenseRepository
{
    public function saveCategory(ExpenseCategory $category): void;

    public function saveExpense(Expense $expense): void;

    public function saveRecurringBill(RecurringBill $bill): void;

    public function getCategory(string $householdId, string $categoryId): ExpenseCategory;

    public function getExpense(string $householdId, string $expenseId): Expense;

    public function getRecurringBill(string $householdId, string $billId): RecurringBill;

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
}
