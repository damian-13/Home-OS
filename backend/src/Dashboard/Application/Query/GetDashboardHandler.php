<?php

namespace App\Dashboard\Application\Query;

use App\Dashboard\Application\Dto\DashboardAttentionItemView;
use App\Dashboard\Application\Dto\DashboardView;
use App\Expenses\Application\Query\GetExpenseOverviewHandler;
use App\Expenses\Application\Query\GetExpenseOverviewQuery;
use App\Health\Domain\Model\BloodTestMarker;
use App\Health\Domain\Repository\HealthRepository;
use App\Home\Domain\Model\HomeMaintenanceTask;
use App\Home\Domain\Repository\HomeMaintenanceRepository;
use App\Shared\Application\Query\QueryHandler;
use DateTimeImmutable;

final readonly class GetDashboardHandler implements QueryHandler
{
    public function __construct(
        private GetExpenseOverviewHandler $expenseOverview,
        private HealthRepository $health,
        private HomeMaintenanceRepository $homeTasks,
    ) {
    }

    public function handles(): string
    {
        return GetDashboardQuery::class;
    }

    public function __invoke(GetDashboardQuery $query): DashboardView
    {
        $month = (new DateTimeImmutable())->format('Y-m');
        $expenses = ($this->expenseOverview)(new GetExpenseOverviewQuery($query->householdId, $month));
        $latestBloodTests = $this->health->latestBloodTests($query->householdId, null, 20);
        $outOfRangeMarkers = $this->health->latestOutOfRangeMarkers($query->householdId, null, 20);
        $markerNames = $this->health->markerNames($query->householdId);
        $today = new DateTimeImmutable('today');
        $overdueHomeTasks = $this->homeTasks->overdueTasks($query->householdId, $today, 5);
        $upcomingHomeTasks = $this->homeTasks->upcomingTasks($query->householdId, $today, 14, 5);
        $attention = [];

        if ($expenses->projectedMonthEndBalance < 0) {
            $attention[] = new DashboardAttentionItemView(
                'expenses-negative-projection',
                'expenses',
                'critical',
                'Projected balance is negative',
                sprintf('Month-end estimate is %.2f PLN after spending and planned bills.', $expenses->projectedMonthEndBalance),
                'Open overview',
                'expenses',
                'overview',
            );
        }

        $overdueBills = count($expenses->billChecklist['overdue'] ?? []);
        if ($overdueBills > 0) {
            $attention[] = new DashboardAttentionItemView(
                'expenses-overdue-bills',
                'expenses',
                'critical',
                sprintf('%d recurring bill%s overdue', $overdueBills, $overdueBills === 1 ? ' is' : 's are'),
                'Open the bill checklist and mark paid, skipped, or planned.',
                'Review bills',
                'expenses',
                'bills',
            );
        }

        foreach (array_slice($overdueHomeTasks, 0, 3) as $task) {
            $attention[] = new DashboardAttentionItemView(
                sprintf('home-overdue-%s', $task->id()),
                'home',
                $task->priority() === HomeMaintenanceTask::PRIORITY_HIGH ? 'critical' : 'warning',
                sprintf('Home task overdue: %s', $task->title()),
                sprintf('%s was due on %s.', $task->area(), $task->nextDueAt()->format('Y-m-d')),
                'Open Home',
                'home',
            );
        }

        if (count($outOfRangeMarkers) > 0) {
            $attention[] = new DashboardAttentionItemView(
                'health-out-of-range',
                'health',
                'critical',
                sprintf('%d health marker%s out of range', count($outOfRangeMarkers), count($outOfRangeMarkers) === 1 ? ' is' : 's are'),
                $this->markerSummary($outOfRangeMarkers),
                'Open health',
                'health',
            );
        }

        $overBudget = array_values(array_filter($expenses->budgetUsage, static fn (array $row): bool => (bool) $row['overBudget']));
        if (count($overBudget) > 0) {
            $attention[] = new DashboardAttentionItemView(
                'expenses-over-budget',
                'expenses',
                'warning',
                sprintf('%d budget categor%s over limit', count($overBudget), count($overBudget) === 1 ? 'y is' : 'ies are'),
                implode(', ', array_map(static fn (array $row): string => $row['category']->name, array_slice($overBudget, 0, 3))),
                'Review budgets',
                'expenses',
                'budgets',
            );
        }

        if (($expenses->review['needsReviewCount'] ?? 0) > 0) {
            $attention[] = new DashboardAttentionItemView(
                'expenses-import-review',
                'expenses',
                'warning',
                sprintf('%d imported transaction%s need review', $expenses->review['needsReviewCount'], $expenses->review['needsReviewCount'] === 1 ? '' : 's'),
                sprintf('%d expenses and %d income rows should be checked before trusting reports.', $expenses->review['expenseNeedsReviewCount'], $expenses->review['incomeNeedsReviewCount']),
                'Review imports',
                'expenses',
                'import-review',
            );
        }

        if (($expenses->review['excludedIncomeTotal'] ?? 0) > 0) {
            $attention[] = new DashboardAttentionItemView(
                'expenses-excluded-income',
                'expenses',
                'info',
                'Some income is excluded from balance',
                sprintf('Transfers/refunds excluded this month: %.2f PLN.', $expenses->review['excludedIncomeTotal']),
                'Open imports',
                'expenses',
                'import-review',
            );
        }

        foreach (array_slice($upcomingHomeTasks, 0, 3) as $task) {
            $attention[] = new DashboardAttentionItemView(
                sprintf('home-upcoming-%s', $task->id()),
                'home',
                'info',
                sprintf('Home task due soon: %s', $task->title()),
                sprintf('%s is due on %s.', $task->area(), $task->nextDueAt()->format('Y-m-d')),
                'Open Home',
                'home',
            );
        }

        $healthReviewMarkers = $this->healthReviewMarkers($latestBloodTests);
        if (count($healthReviewMarkers) > 0) {
            $attention[] = new DashboardAttentionItemView(
                'health-marker-review',
                'health',
                'warning',
                sprintf('%d health marker%s need cleanup', count($healthReviewMarkers), count($healthReviewMarkers) === 1 ? '' : 's'),
                'Some imported markers have unknown status, missing units, or suspicious reference ranges.',
                'Review health',
                'health',
            );
        }

        $staleMarkerNames = $this->staleMarkerNames($query->householdId, $markerNames);
        if (count($staleMarkerNames) > 0) {
            $attention[] = new DashboardAttentionItemView(
                'health-stale-markers',
                'health',
                'info',
                sprintf('%d marker%s not checked for over a year', count($staleMarkerNames), count($staleMarkerNames) === 1 ? ' was' : 's were'),
                implode(', ', array_slice($staleMarkerNames, 0, 4)),
                'Open health',
                'health',
            );
        }

        return new DashboardView(
            'Home OS',
            'online',
            [
                'homeTasksDue' => count($overdueHomeTasks) + count($upcomingHomeTasks),
                'monthlySpend' => $expenses->monthTotal,
                'projectedBalance' => $expenses->projectedMonthEndBalance,
                'financeReviewCount' => (int) ($expenses->review['needsReviewCount'] ?? 0),
                'healthMarkersTracked' => count($markerNames),
                'healthOutOfRange' => count($outOfRangeMarkers),
                'documentsStored' => count($this->health->latestDocuments($query->householdId, null, 100)),
            ],
            $attention,
        );
    }

    /**
     * @param list<object> $bloodTests
     * @return list<BloodTestMarker>
     */
    private function healthReviewMarkers(array $bloodTests): array
    {
        $markers = [];
        foreach ($bloodTests as $bloodTest) {
            foreach ($bloodTest->markers() as $marker) {
                if ($marker->status() === 'unknown' || trim($marker->unit()) === '' || ($marker->referenceMin() !== null && $marker->referenceMax() !== null && $marker->referenceMin() > $marker->referenceMax())) {
                    $markers[] = $marker;
                }
            }
        }

        return $markers;
    }

    /**
     * @param list<string> $markerNames
     * @return list<string>
     */
    private function staleMarkerNames(string $householdId, array $markerNames): array
    {
        $stale = [];
        $cutoff = new DateTimeImmutable('-12 months');
        foreach ($markerNames as $markerName) {
            $latest = $this->health->markerHistory($householdId, $markerName, null, 1)[0] ?? null;
            if ($latest instanceof BloodTestMarker && $latest->bloodTest()->testedAt() < $cutoff) {
                $stale[] = $markerName;
            }
        }

        return $stale;
    }

    /**
     * @param list<BloodTestMarker> $markers
     */
    private function markerSummary(array $markers): string
    {
        return implode(', ', array_map(
            static fn (BloodTestMarker $marker): string => sprintf('%s %s', $marker->name(), $marker->status()),
            array_slice($markers, 0, 4),
        ));
    }
}
