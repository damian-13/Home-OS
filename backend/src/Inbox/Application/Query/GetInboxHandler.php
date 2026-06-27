<?php

namespace App\Inbox\Application\Query;

use App\Expenses\Application\Dto\ExpenseView;
use App\Expenses\Application\Dto\IncomeEntryView;
use App\Expenses\Application\Query\GetExpenseOverviewHandler;
use App\Expenses\Application\Query\GetExpenseOverviewQuery;
use App\Health\Domain\Model\BloodTest;
use App\Health\Domain\Model\BloodTestMarker;
use App\Health\Domain\Repository\HealthRepository;
use App\Home\Domain\Model\HomeMaintenanceTask;
use App\Home\Domain\Repository\HomeMaintenanceRepository;
use App\Inbox\Application\Dto\InboxItemView;
use App\Inbox\Application\Dto\InboxView;
use App\Shared\Application\Query\QueryHandler;
use DateTimeImmutable;

final readonly class GetInboxHandler implements QueryHandler
{
    public function __construct(
        private GetExpenseOverviewHandler $expenseOverview,
        private HealthRepository $health,
        private HomeMaintenanceRepository $homeTasks,
    ) {
    }

    public function handles(): string
    {
        return GetInboxQuery::class;
    }

    public function __invoke(GetInboxQuery $query): InboxView
    {
        $now = new DateTimeImmutable();
        $month = $now->format('Y-m');
        $expenses = ($this->expenseOverview)(new GetExpenseOverviewQuery($query->householdId, $month));
        $latestBloodTests = $this->health->latestBloodTests($query->householdId, null, 20);
        $outOfRangeMarkers = $this->health->latestOutOfRangeMarkers($query->householdId, null, 20);
        $today = new DateTimeImmutable('today');
        $items = [];

        foreach (($expenses->review['expenseCandidates'] ?? []) as $expense) {
            if (!$expense instanceof ExpenseView) {
                continue;
            }

            $items[] = new InboxItemView(
                sprintf('expenses-review-%s', $expense->id),
                'expenses',
                'expense_review',
                $expense->id,
                'warning',
                null,
                sprintf('Review expense: %s', $expense->description),
                sprintf('%s · %.2f %s · %s', $expense->spentOn, $expense->amount, $expense->currency, $expense->reviewReason ?? 'Imported expense needs category check'),
                'Review import',
                '#expenses',
                'expenses',
                'import-review',
                $expense->spentOn,
                null,
                $expense->reviewStatus,
            );
        }

        foreach (($expenses->review['incomeCandidates'] ?? []) as $entry) {
            if (!$entry instanceof IncomeEntryView) {
                continue;
            }

            $items[] = new InboxItemView(
                sprintf('income-review-%s', $entry->id),
                'expenses',
                'income_review',
                $entry->id,
                'warning',
                null,
                sprintf('Review income: %s', $entry->description),
                sprintf('%s · %.2f %s · %s', $entry->receivedOn, $entry->amount, $entry->currency, $entry->reviewReason ?? 'Imported income needs type check'),
                'Review income',
                '#expenses',
                'expenses',
                'import-review',
                $entry->receivedOn,
                null,
                $entry->reviewStatus,
            );
        }

        foreach ($outOfRangeMarkers as $marker) {
            $items[] = new InboxItemView(
                sprintf('health-out-of-range-%s', $marker->id()),
                'health',
                'blood_marker',
                $marker->id(),
                'critical',
                null,
                sprintf('%s is %s', $marker->name(), $marker->status()),
                sprintf('%s: %.2f %s from %s.', $marker->bloodTest()->testedAt()->format('Y-m-d'), $marker->value(), $marker->unit(), $marker->bloodTest()->memberId()),
                'Open health',
                '#health',
                'health',
                null,
                $marker->bloodTest()->testedAt()->format('Y-m-d'),
                null,
                $marker->status(),
            );
        }

        foreach ($this->healthReviewMarkers($latestBloodTests) as $marker) {
            $items[] = new InboxItemView(
                sprintf('health-review-%s', $marker->id()),
                'health',
                'blood_marker_review',
                $marker->id(),
                'warning',
                null,
                sprintf('Review marker data: %s', $marker->name()),
                'Unknown status, missing unit, or suspicious reference range needs cleanup.',
                'Review health',
                '#health',
                'health',
                null,
                $marker->bloodTest()->testedAt()->format('Y-m-d'),
                null,
                $marker->status(),
            );
        }

        foreach ($this->homeTasks->overdueTasks($query->householdId, $today, 20) as $task) {
            $items[] = $this->homeTaskItem($task, $task->priority() === HomeMaintenanceTask::PRIORITY_HIGH ? 'critical' : 'warning', 'Home task overdue', 'Mark done');
        }

        foreach ($this->homeTasks->upcomingTasks($query->householdId, $today, 14, 20) as $task) {
            $items[] = $this->homeTaskItem($task, 'info', 'Home task due soon', 'Open Home');
        }

        usort($items, static function (InboxItemView $left, InboxItemView $right): int {
            $severityRank = ['critical' => 0, 'warning' => 1, 'info' => 2];
            $severityCompare = ($severityRank[$left->severity] ?? 9) <=> ($severityRank[$right->severity] ?? 9);

            if ($severityCompare !== 0) {
                return $severityCompare;
            }

            return ($left->dueAt ?? $left->detectedAt) <=> ($right->dueAt ?? $right->detectedAt);
        });

        return new InboxView($items, [
            'total' => count($items),
            'critical' => count(array_filter($items, static fn (InboxItemView $item): bool => $item->severity === 'critical')),
            'warning' => count(array_filter($items, static fn (InboxItemView $item): bool => $item->severity === 'warning')),
            'info' => count(array_filter($items, static fn (InboxItemView $item): bool => $item->severity === 'info')),
            'highestSeverity' => $this->highestSeverity($items),
        ]);
    }

    private function homeTaskItem(HomeMaintenanceTask $task, string $severity, string $titlePrefix, string $targetAction): InboxItemView
    {
        return new InboxItemView(
            sprintf('home-task-%s', $task->id()),
            'home',
            'maintenance_task',
            $task->id(),
            $severity,
            null,
            sprintf('%s: %s', $titlePrefix, $task->title()),
            sprintf('%s · due %s · priority %s.', $task->area(), $task->nextDueAt()->format('Y-m-d'), $task->priority()),
            $targetAction,
            '#home',
            'home',
            null,
            $task->createdAt()->format(DATE_ATOM),
            $task->nextDueAt()->format('Y-m-d'),
            $task->status(),
        );
    }

    /**
     * @param list<BloodTest> $bloodTests
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
     * @param list<InboxItemView> $items
     */
    private function highestSeverity(array $items): ?string
    {
        foreach (['critical', 'warning', 'info'] as $severity) {
            foreach ($items as $item) {
                if ($item->severity === $severity) {
                    return $severity;
                }
            }
        }

        return null;
    }
}
