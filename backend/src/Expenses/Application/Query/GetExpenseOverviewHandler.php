<?php

namespace App\Expenses\Application\Query;

use App\Expenses\Application\DefaultExpenseCategories;
use App\Expenses\Application\Dto\ExpenseCategoryView;
use App\Expenses\Application\Dto\ExpenseOverviewView;
use App\Expenses\Application\Dto\ExpenseView;
use App\Expenses\Application\Dto\IncomeEntryView;
use App\Expenses\Application\Dto\IncomeSourceView;
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
        $incomeSources = $this->expenses->incomeSourcesForHousehold($query->householdId);
        $incomeEntries = $this->expenses->incomeEntriesBetween($query->householdId, $monthStart, $monthEnd);
        $budgets = $this->expenses->budgetsForMonth($query->householdId, $month);
        $payments = $this->expenses->recurringBillPaymentsForMonth($query->householdId, $month);

        $monthTotalCents = array_sum(array_map(static fn ($expense) => $expense->amountCents(), $monthExpenses));
        $recurringTotalCents = array_sum(array_map(static fn ($bill) => $bill->amountCents(), $recurringBills));
        $expectedIncomeCents = array_sum(array_map(static fn ($source) => $source->active() ? $source->amountCents() : 0, $incomeSources));
        $actualIncomeCents = array_sum(array_map(static fn ($entry) => $entry->amountCents(), $incomeEntries));

        $categoryTotals = [];
        foreach ($categories as $category) {
            $categoryTotals[$category->id()] = [
                'name' => $category->name(),
                'color' => $category->color(),
                'amount' => 0.0,
            ];
        }

        foreach ($monthExpenses as $expense) {
            if (!isset($categoryTotals[$expense->category()->id()])) {
                $categoryTotals[$expense->category()->id()] = [
                    'name' => $expense->category()->name(),
                    'color' => $expense->category()->color(),
                    'amount' => 0.0,
                ];
            }
            $categoryTotals[$expense->category()->id()]['amount'] += $expense->amountCents() / 100;
        }
        $budgetByCategory = [];
        foreach ($budgets as $budget) {
            $budgetByCategory[$budget->category()->id()] = $budget;
        }
        $paymentByBill = [];
        foreach ($payments as $payment) {
            $paymentByBill[$payment->billId()] = $payment;
        }
        $billChecklist = ['upcoming' => [], 'paid' => [], 'overdue' => [], 'skipped' => []];
        $paidBillsCents = 0;
        $today = new DateTimeImmutable();
        foreach ($recurringBills as $bill) {
            $payment = $paymentByBill[$bill->id()] ?? null;
            $amountCents = $payment?->amountOverrideCents() ?? $bill->amountCents();
            $status = $payment?->status() ?? 'planned';
            $bucket = $status;
            if ($status === 'planned') {
                $dueDate = new DateTimeImmutable(sprintf('%s-%02d', $month, min($bill->dueDay(), (int) $monthEnd->format('d'))));
                $bucket = $dueDate < $today ? 'overdue' : 'upcoming';
            }
            if ($status === 'paid') {
                $paidBillsCents += $amountCents;
            }
            $billChecklist[$bucket][] = [
                'bill' => RecurringBillView::fromBill($bill),
                'status' => $status,
                'amount' => $amountCents / 100,
                'paidOn' => $payment?->paidOn()?->format('Y-m-d'),
            ];
        }
        $budgetUsage = [];
        foreach ($categories as $category) {
            $budgetCents = isset($budgetByCategory[$category->id()]) ? $budgetByCategory[$category->id()]->amountCents() : 0;
            $spentCents = (int) round(($categoryTotals[$category->id()]['amount'] ?? 0) * 100);
            $budgetUsage[] = [
                'category' => ExpenseCategoryView::fromCategory($category),
                'budget' => $budgetCents / 100,
                'spent' => $spentCents / 100,
                'remaining' => ($budgetCents - $spentCents) / 100,
                'percent' => $budgetCents > 0 ? min(200, round(($spentCents / $budgetCents) * 100, 1)) : 0,
                'overBudget' => $budgetCents > 0 && $spentCents > $budgetCents,
            ];
        }
        $topCategories = array_values(array_filter($categoryTotals, static fn (array $total) => $total['amount'] > 0));
        usort($topCategories, static fn (array $left, array $right): int => $right['amount'] <=> $left['amount']);
        $memberTotals = [];
        foreach ($monthExpenses as $expense) {
            $key = $expense->paidByMemberId() ?? 'household';
            $memberTotals[$key] ??= ['memberId' => $expense->paidByMemberId(), 'amount' => 0.0];
            $memberTotals[$key]['amount'] += $expense->amountCents() / 100;
        }
        $plannedBillsCents = array_sum(array_map(static fn ($bill) => $bill->amountCents(), $recurringBills));
        $incomeForProjectionCents = max($expectedIncomeCents, $actualIncomeCents);

        return new ExpenseOverviewView(
            'PLN',
            $monthTotalCents / 100,
            $recurringTotalCents / 100,
            array_map(static fn (ExpenseCategory $category) => ExpenseCategoryView::fromCategory($category), $categories),
            array_map(static fn ($expense) => ExpenseView::fromExpense($expense), $latestExpenses),
            array_map(static fn ($bill) => RecurringBillView::fromBill($bill), $recurringBills),
            array_values(array_filter($categoryTotals, static fn (array $total) => $total['amount'] > 0)),
            $expectedIncomeCents / 100,
            $actualIncomeCents / 100,
            $monthTotalCents / 100,
            $plannedBillsCents / 100,
            $paidBillsCents / 100,
            ($incomeForProjectionCents - $monthTotalCents - $plannedBillsCents) / 100,
            ($incomeForProjectionCents - $monthTotalCents - $plannedBillsCents) / 100,
            array_map(static fn ($source) => IncomeSourceView::fromSource($source), $incomeSources),
            array_map(static fn ($entry) => IncomeEntryView::fromEntry($entry), $incomeEntries),
            $budgetUsage,
            $billChecklist,
            array_slice($topCategories, 0, 5),
            array_values($memberTotals),
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
