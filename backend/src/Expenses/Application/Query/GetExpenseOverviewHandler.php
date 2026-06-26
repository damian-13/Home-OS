<?php

namespace App\Expenses\Application\Query;

use App\Expenses\Application\DefaultExpenseCategories;
use App\Expenses\Application\Dto\ExpenseCategoryView;
use App\Expenses\Application\Dto\ExpenseOverviewView;
use App\Expenses\Application\Dto\ExpenseView;
use App\Expenses\Application\Dto\FinanceReviewBatchView;
use App\Expenses\Application\Dto\FinanceReviewRuleView;
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
        $trendStart = $monthStart->modify('-11 months');
        $monthExpenses = $this->expenses->expensesBetween($query->householdId, $monthStart, $monthEnd, $categoryId, $paidByMemberId);
        $trendExpenses = $this->expenses->expensesBetween($query->householdId, $trendStart, $monthEnd);
        $latestExpenses = $this->expenses->latestExpenses($query->householdId, $monthStart, $monthEnd, $categoryId, $paidByMemberId, 250);
        $recurringBills = $this->expenses->recurringBillsForHousehold($query->householdId, $categoryId, $paidByMemberId);
        $incomeSources = $this->expenses->incomeSourcesForHousehold($query->householdId);
        $incomeEntries = $this->expenses->incomeEntriesBetween($query->householdId, $monthStart, $monthEnd);
        $trendIncomeEntries = $this->expenses->incomeEntriesBetween($query->householdId, $trendStart, $monthEnd);
        $budgets = $this->expenses->budgetsForMonth($query->householdId, $month);
        $payments = $this->expenses->recurringBillPaymentsForMonth($query->householdId, $month);

        $monthTotalCents = array_sum(array_map(static fn ($expense) => $expense->amountCents(), $monthExpenses));
        $recurringTotalCents = array_sum(array_map(static fn ($bill) => $bill->amountCents(), $recurringBills));
        $expectedIncomeCents = array_sum(array_map(static fn ($source) => $source->active() ? $source->amountCents() : 0, $incomeSources));
        $actualIncomeCents = array_sum(array_map(static fn ($entry) => self::isRealIncomeEntry($entry) ? $entry->amountCents() : 0, $incomeEntries));

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
        $dailyTotals = [];
        foreach ($monthExpenses as $expense) {
            $day = $expense->spentOn()->format('Y-m-d');
            $dailyTotals[$day] ??= ['date' => $day, 'expense' => 0.0, 'income' => 0.0];
            $dailyTotals[$day]['expense'] += $expense->amountCents() / 100;
        }
        foreach ($incomeEntries as $entry) {
            if (!self::isRealIncomeEntry($entry)) {
                continue;
            }

            $day = $entry->receivedOn()->format('Y-m-d');
            $dailyTotals[$day] ??= ['date' => $day, 'expense' => 0.0, 'income' => 0.0];
            $dailyTotals[$day]['income'] += $entry->amountCents() / 100;
        }
        ksort($dailyTotals);
        $monthlyTrend = [];
        for ($cursor = $trendStart; $cursor <= $monthStart; $cursor = $cursor->modify('+1 month')) {
            $monthKey = $cursor->format('Y-m');
            $monthlyTrend[$monthKey] = ['month' => $monthKey, 'expense' => 0.0, 'income' => 0.0, 'balance' => 0.0];
        }
        foreach ($trendExpenses as $expense) {
            $monthKey = $expense->spentOn()->format('Y-m');
            if (isset($monthlyTrend[$monthKey])) {
                $monthlyTrend[$monthKey]['expense'] += $expense->amountCents() / 100;
            }
        }
        foreach ($trendIncomeEntries as $entry) {
            if (!self::isRealIncomeEntry($entry)) {
                continue;
            }

            $monthKey = $entry->receivedOn()->format('Y-m');
            if (isset($monthlyTrend[$monthKey])) {
                $monthlyTrend[$monthKey]['income'] += $entry->amountCents() / 100;
            }
        }
        foreach ($monthlyTrend as &$trendMonth) {
            $trendMonth['balance'] = $trendMonth['income'] - $trendMonth['expense'];
        }
        unset($trendMonth);
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
        $latestUndoableBatch = $this->expenses->latestUndoableReviewBatch($query->householdId);
        $expenseReviewCandidates = array_values(array_filter(
            $monthExpenses,
            static fn ($expense) => $expense->reviewStatus() === 'needs_review',
        ));
        $incomeReviewCandidates = array_values(array_filter(
            $incomeEntries,
            static fn ($entry) => $entry->reviewStatus() === 'needs_review',
        ));
        $transferLikeIncomeCents = array_sum(array_map(
            static fn ($entry) => in_array($entry->incomeKind(), ['transfer', 'refund'], true) ? $entry->amountCents() : 0,
            $incomeEntries,
        ));

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
            array_values($dailyTotals),
            array_values($monthlyTrend),
            array_map(static fn ($rule) => FinanceReviewRuleView::fromRule($rule), $this->expenses->reviewRulesForHousehold($query->householdId)),
            [
                'needsReviewCount' => count($expenseReviewCandidates) + count($incomeReviewCandidates),
                'expenseNeedsReviewCount' => count($expenseReviewCandidates),
                'incomeNeedsReviewCount' => count($incomeReviewCandidates),
                'excludedIncomeTotal' => $transferLikeIncomeCents / 100,
                'lastAppliedBatch' => $latestUndoableBatch ? FinanceReviewBatchView::fromBatch($latestUndoableBatch) : null,
                'expenseCandidates' => array_map(static fn ($expense) => ExpenseView::fromExpense($expense), array_slice($expenseReviewCandidates, 0, 12)),
                'incomeCandidates' => array_map(static fn ($entry) => IncomeEntryView::fromEntry($entry), array_slice($incomeReviewCandidates, 0, 12)),
            ],
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

    private static function isRealIncomeEntry($entry): bool
    {
        return in_array($entry->incomeKind(), ['salary', 'other'], true);
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
