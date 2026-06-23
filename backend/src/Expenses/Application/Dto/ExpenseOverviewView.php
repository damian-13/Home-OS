<?php

namespace App\Expenses\Application\Dto;

final readonly class ExpenseOverviewView
{
    /**
     * @param list<ExpenseCategoryView> $categories
     * @param list<ExpenseView> $latestExpenses
     * @param list<RecurringBillView> $recurringBills
     * @param list<array{name: string, color: string, amount: float}> $byCategory
     * @param list<IncomeSourceView> $incomeSources
     * @param list<IncomeEntryView> $incomeEntries
     * @param list<array{category: ExpenseCategoryView, budget: float, spent: float, remaining: float, percent: float, overBudget: bool}> $budgetUsage
     * @param array{upcoming: list<array<string, mixed>>, paid: list<array<string, mixed>>, overdue: list<array<string, mixed>>, skipped: list<array<string, mixed>>} $billChecklist
     * @param list<array{name: string, color: string, amount: float}> $topCategories
     * @param list<array{memberId: ?string, amount: float}> $memberTotals
     * @param list<array{date: string, expense: float, income: float}> $dailySpending
     * @param list<array{month: string, expense: float, income: float, balance: float}> $monthlyTrend
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
        public float $expectedIncome,
        public float $actualIncome,
        public float $spentTotal,
        public float $recurringPlannedTotal,
        public float $paidBillsTotal,
        public float $remainingMonthlyMoney,
        public float $projectedMonthEndBalance,
        public array $incomeSources,
        public array $incomeEntries,
        public array $budgetUsage,
        public array $billChecklist,
        public array $topCategories,
        public array $memberTotals,
        public array $dailySpending,
        public array $monthlyTrend,
        public array $activeFilters,
    ) {
    }
}
