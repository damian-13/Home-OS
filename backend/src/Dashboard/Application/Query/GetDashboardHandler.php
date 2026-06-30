<?php

namespace App\Dashboard\Application\Query;

use App\Dashboard\Application\Dto\DashboardAttentionItemView;
use App\Dashboard\Application\Dto\DashboardView;
use App\Documents\Domain\Repository\DocumentRepository;
use App\Expenses\Application\Query\GetExpenseOverviewHandler;
use App\Expenses\Application\Query\GetExpenseOverviewQuery;
use App\Health\Application\Query\GetHealthReviewHandler;
use App\Health\Application\Query\GetHealthReviewQuery;
use App\Health\Domain\Model\BloodTestMarker;
use App\Health\Domain\Repository\HealthRepository;
use App\Home\Domain\Model\HomeMaintenanceTask;
use App\Home\Domain\Repository\HomeMaintenanceRepository;
use App\Inbox\Application\Query\GetInboxHandler;
use App\Inbox\Application\Query\GetInboxQuery;
use App\Reminders\Domain\Model\Reminder;
use App\Reminders\Domain\Repository\ReminderRepository;
use App\Shared\Application\Query\QueryHandler;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class GetDashboardHandler implements QueryHandler
{
    public function __construct(
        private GetExpenseOverviewHandler $expenseOverview,
        private HealthRepository $health,
        private GetHealthReviewHandler $healthReview,
        private HomeMaintenanceRepository $homeTasks,
        private GetInboxHandler $inbox,
        private ReminderRepository $reminders,
        private DocumentRepository $documents,
        private LoggerInterface $logger,
    ) {
    }

    public function handles(): string
    {
        return GetDashboardQuery::class;
    }

    public function __invoke(GetDashboardQuery $query): DashboardView
    {
        $startedAt = microtime(true);

        try {
            $month = (new DateTimeImmutable())->format('Y-m');
            $expenses = ($this->expenseOverview)(new GetExpenseOverviewQuery($query->householdId, $month));
            $markerNames = $this->health->markerNames($query->householdId);
            $healthReview = ($this->healthReview)(new GetHealthReviewQuery($query->householdId));
            $outOfRangeReviewItems = array_values(array_filter(
                $healthReview['items'],
                static fn (object $item): bool => property_exists($item, 'type') && $item->type === 'out_of_range_result',
            ));
            $today = new DateTimeImmutable('today');
            $overdueHomeTasks = $this->homeTasks->overdueTasks($query->householdId, $today, 5);
            $upcomingHomeTasks = $this->homeTasks->upcomingTasks($query->householdId, $today, 14, 5);
            $overdueReminders = $this->reminders->overdueReminders($query->householdId, $today, 5);
            $todayReminders = $this->reminders->dueTodayReminders($query->householdId, $today, 5);
            $upcomingReminders = $this->reminders->upcomingReminders($query->householdId, $today, 14, 5);
            $expiredDocuments = $this->documents->expiredDocuments($query->householdId, $today, 5);
            $expiringDocuments = $this->documents->expiringDocuments($query->householdId, $today, 30, 5);
            $inbox = ($this->inbox)(new GetInboxQuery($query->householdId));
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

        foreach (array_slice($overdueReminders, 0, 3) as $reminder) {
            $attention[] = new DashboardAttentionItemView(
                sprintf('reminder-overdue-%s', $reminder->id()),
                'reminders',
                $reminder->priority() === Reminder::PRIORITY_HIGH ? 'critical' : 'warning',
                sprintf('Reminder overdue: %s', $reminder->title()),
                sprintf('Was due on %s.', $reminder->dueAt()->format('Y-m-d')),
                'Open reminders',
                'reminders',
            );
        }

        foreach (array_slice($todayReminders, 0, 3) as $reminder) {
            $attention[] = new DashboardAttentionItemView(
                sprintf('reminder-today-%s', $reminder->id()),
                'reminders',
                'warning',
                sprintf('Reminder due today: %s', $reminder->title()),
                $reminder->note() ?? 'Complete or skip this reminder today.',
                'Open reminders',
                'reminders',
            );
        }

        foreach (array_slice($expiredDocuments, 0, 3) as $document) {
            $attention[] = new DashboardAttentionItemView(
                sprintf('document-expired-%s', $document->id()),
                'documents',
                'critical',
                sprintf('Document expired: %s', $document->title()),
                sprintf('%s expired on %s.', $document->type(), $document->expiresAt()?->format('Y-m-d')),
                'Open documents',
                'documents',
            );
        }

        if (count($outOfRangeReviewItems) > 0) {
            $attention[] = new DashboardAttentionItemView(
                'health-out-of-range',
                'health',
                'critical',
                sprintf('%d health marker%s out of range', count($outOfRangeReviewItems), count($outOfRangeReviewItems) === 1 ? ' is' : 's are'),
                $this->healthReviewSummary($outOfRangeReviewItems),
                'Open review',
                'health-review',
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

        foreach (array_slice($upcomingReminders, 0, 3) as $reminder) {
            $attention[] = new DashboardAttentionItemView(
                sprintf('reminder-upcoming-%s', $reminder->id()),
                'reminders',
                'info',
                sprintf('Reminder due soon: %s', $reminder->title()),
                sprintf('Due on %s.', $reminder->dueAt()->format('Y-m-d')),
                'Open reminders',
                'reminders',
            );
        }

        foreach (array_slice($expiringDocuments, 0, 3) as $document) {
            $attention[] = new DashboardAttentionItemView(
                sprintf('document-expiring-%s', $document->id()),
                'documents',
                'warning',
                sprintf('Document expires soon: %s', $document->title()),
                sprintf('%s expires on %s.', $document->type(), $document->expiresAt()?->format('Y-m-d')),
                'Open documents',
                'documents',
            );
        }

        if ($healthReview['summary']['total'] > 0) {
            $attention[] = new DashboardAttentionItemView(
                'health-review',
                'health',
                $healthReview['summary']['critical'] > 0 ? 'critical' : 'warning',
                sprintf('%d health review item%s need cleanup', $healthReview['summary']['total'], $healthReview['summary']['total'] === 1 ? '' : 's'),
                sprintf('%d critical and %d warning health data-quality items are waiting.', $healthReview['summary']['critical'], $healthReview['summary']['warning']),
                'Open review',
                'health-review',
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
                'Open review',
                'health-review',
            );
        }

            $attention = $this->sortAttention($attention);

            $view = new DashboardView(
            'Home OS',
            'online',
            [
                'homeTasksDue' => count($overdueHomeTasks) + count($upcomingHomeTasks),
                'remindersDue' => count($overdueReminders) + count($todayReminders) + count($upcomingReminders),
                'inboxItemsDue' => $inbox->summary['total'],
                'inboxHighestSeverity' => $inbox->summary['highestSeverity'],
                'monthlySpend' => $expenses->monthTotal,
                'projectedBalance' => $expenses->projectedMonthEndBalance,
                'financeReviewCount' => (int) ($expenses->review['needsReviewCount'] ?? 0),
                'healthMarkersTracked' => count($markerNames),
                'healthOutOfRange' => count($outOfRangeReviewItems),
                'healthReviewCount' => $healthReview['summary']['total'],
                'healthReviewCritical' => $healthReview['summary']['critical'],
                'documentsStored' => $this->documents->countDocuments($query->householdId),
            ],
            $attention,
            );

            $this->logger->info('Dashboard aggregation completed.', [
                'householdId' => $query->householdId,
                'durationMs' => round((microtime(true) - $startedAt) * 1000, 1),
                'attentionCount' => count($attention),
            ]);

            return $view;
        } catch (Throwable $exception) {
            $this->logger->error('Dashboard aggregation failed.', [
                'householdId' => $query->householdId,
                'durationMs' => round((microtime(true) - $startedAt) * 1000, 1),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
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
     * @param list<object> $items
     */
    private function healthReviewSummary(array $items): string
    {
        return implode(', ', array_map(
            static fn (object $item): string => property_exists($item, 'title') ? $item->title : 'Health review item',
            array_slice($items, 0, 4),
        ));
    }

    /**
     * @param list<DashboardAttentionItemView> $attention
     * @return list<DashboardAttentionItemView>
     */
    private function sortAttention(array $attention): array
    {
        usort($attention, static function (DashboardAttentionItemView $left, DashboardAttentionItemView $right): int {
            $severityRank = ['critical' => 0, 'warning' => 1, 'info' => 2];
            $areaRank = [
                'home' => 0,
                'reminders' => 1,
                'health' => 2,
                'expenses' => 3,
                'documents' => 4,
            ];

            return ($severityRank[$left->severity] ?? 9) <=> ($severityRank[$right->severity] ?? 9)
                ?: ($areaRank[$left->area] ?? 9) <=> ($areaRank[$right->area] ?? 9);
        });

        return $attention;
    }
}
