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
        $now = new DateTimeImmutable();
        $monthStart = $now->modify('first day of this month')->setTime(0, 0);
        $monthEnd = $now->modify('last day of this month')->setTime(23, 59, 59);
        $monthExpenses = $this->expenses->expensesBetween($query->householdId, $monthStart, $monthEnd);
        $latestExpenses = $this->expenses->latestExpenses($query->householdId);
        $recurringBills = $this->expenses->recurringBillsForHousehold($query->householdId);

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
        );
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
