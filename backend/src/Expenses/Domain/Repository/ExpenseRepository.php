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

    /**
     * @return list<ExpenseCategory>
     */
    public function categoriesForHousehold(string $householdId): array;

    /**
     * @return list<Expense>
     */
    public function latestExpenses(string $householdId, int $limit = 20): array;

    /**
     * @return list<Expense>
     */
    public function expensesBetween(string $householdId, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    /**
     * @return list<RecurringBill>
     */
    public function recurringBillsForHousehold(string $householdId): array;
}
