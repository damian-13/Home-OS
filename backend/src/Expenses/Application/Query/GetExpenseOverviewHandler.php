<?php

namespace App\Expenses\Application\Query;

use App\Expenses\Application\DefaultExpenseCategories;
use App\Expenses\Application\Dto\ExpenseCategoryView;
use App\Expenses\Application\Dto\ExpenseOverviewView;
use App\Expenses\Application\Dto\ExpenseView;
use App\Expenses\Application\Dto\RecurringBillView;
use App\Expenses\Domain\Model\ExpenseCategory;
use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Query\QueryHandler;
use App\Shared\Domain\Uuid;
use DateTimeImmutable;

final readonly class GetExpenseOverviewHandler implements QueryHandler
{
    public function __construct(
        private ExpenseRepository $expenses,
    ) {
    }

    public function handles(): string
    {
        return GetExpenseOverviewQuery::class;
    }

    public function __invoke(GetExpenseOverviewQuery $query): ExpenseOverviewView
    {
        $categories = $this->ensureDefaultCategories($query->householdId);
        $month = $this->normalizeMonth($query->month);
        $monthStart = new DateTimeImmutable($month . '-01 00:00:00');
        $monthEnd = $monthStart->modify('last day of this month')->setTime(23, 59, 59);
        $categoryId = $query->categoryId ?: null;
        $paidByMemberId = $query->paidByMemberId ?: null;
        $monthExpenses = $this->expenses->expensesBetween($query->householdId, $monthStart, $monthEnd, $categoryId, $paidByMemberId);
        $latestExpenses = $this->expenses->latestExpenses($query->householdId, $monthStart, $monthEnd, $categoryId, $paidByMemberId);
        $recurringBills = $this->expenses->recurringBillsForHousehold($query->householdId, $categoryId, $paidByMemberId);

        $monthTotalCents = array_sum(array_map(static fn ($expense) => $expense->amountCents(), $monthExpenses));
        $recurringTotalCents = array_sum(array_map(static fn ($bill) => $bill->amountCents(), $recurringBills));

        $categoryTotals = [];
        foreach ($categories as $category) {
            $categoryTotals[$category->id()] = [
                'name' => $category->name(),
                'color' => $category->color(),
                'amount' => 0.0,
            ];
        }

        foreach ($monthExpenses as $expense) {
            $categoryTotals[$expense->category()->id()]['amount'] += $expense->amountCents() / 100;
        }

        return new ExpenseOverviewView(
            'PLN',
            $monthTotalCents / 100,
            $recurringTotalCents / 100,
            array_map(static fn (ExpenseCategory $category) => ExpenseCategoryView::fromCategory($category), $categories),
            array_map(static fn ($expense) => ExpenseView::fromExpense($expense), $latestExpenses),
            array_map(static fn ($bill) => RecurringBillView::fromBill($bill), $recurringBills),
            array_values(array_filter($categoryTotals, static fn (array $total) => $total['amount'] > 0)),
            [
                'month' => $month,
                'categoryId' => $categoryId,
                'paidByMemberId' => $paidByMemberId,
            ],
        );
    }

    private function normalizeMonth(?string $month): string
    {
        if ($month && 1 === preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $month;
        }

        return (new DateTimeImmutable())->format('Y-m');
    }

    /**
     * @return list<ExpenseCategory>
     */
    private function ensureDefaultCategories(string $householdId): array
    {
        $categories = $this->expenses->categoriesForHousehold($householdId);

        if ($categories !== []) {
            return $categories;
        }

        foreach (DefaultExpenseCategories::all() as $defaultCategory) {
            $this->expenses->saveCategory(new ExpenseCategory(
                (string) Uuid::new(),
                $householdId,
                $defaultCategory['name'],
                $defaultCategory['slug'],
                $defaultCategory['color'],
            ));
        }

        return $this->expenses->categoriesForHousehold($householdId);
    }
}
