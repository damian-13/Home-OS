<?php

namespace App\Expenses\Application\Dto;

final readonly class ExpenseOverviewView
{
    /**
     * @param list<ExpenseCategoryView> $categories
     * @param list<ExpenseView> $latestExpenses
     * @param list<RecurringBillView> $recurringBills
     * @param list<array{name: string, color: string, amount: float}> $byCategory
     * @param array{month: string, categoryId: ?string, paidByMemberId: ?string} $activeFilters
     */
    public function __construct(
        public string $currency,
        public float $monthTotal,
        public float $recurringMonthlyTotal,
        public array $categories,
        public array $latestExpenses,
        public array $recurringBills,
        public array $byCategory,
        public array $activeFilters,
    ) {
    }
}
