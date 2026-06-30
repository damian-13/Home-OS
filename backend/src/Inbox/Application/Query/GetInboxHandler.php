<?php

namespace App\Inbox\Application\Query;

use App\Expenses\Application\Dto\ExpenseView;
use App\Documents\Domain\Model\Document;
use App\Documents\Domain\Repository\DocumentRepository;
use App\Expenses\Application\Dto\IncomeEntryView;
use App\Expenses\Application\Query\GetExpenseOverviewHandler;
use App\Expenses\Application\Query\GetExpenseOverviewQuery;
use App\Health\Application\Query\GetHealthReviewHandler;
use App\Health\Application\Query\GetHealthReviewQuery;
use App\Home\Domain\Model\HomeMaintenanceTask;
use App\Home\Domain\Repository\HomeMaintenanceRepository;
use App\Inbox\Application\Dto\InboxItemView;
use App\Inbox\Application\Dto\InboxView;
use App\Reminders\Domain\Model\Reminder;
use App\Reminders\Domain\Repository\ReminderRepository;
use App\Shared\Application\Query\QueryHandler;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class GetInboxHandler implements QueryHandler
{
    public function __construct(
        private GetExpenseOverviewHandler $expenseOverview,
        private GetHealthReviewHandler $healthReview,
        private HomeMaintenanceRepository $homeTasks,
        private ReminderRepository $reminders,
        private DocumentRepository $documents,
        private LoggerInterface $logger,
    ) {
    }

    public function handles(): string
    {
        return GetInboxQuery::class;
    }

    public function __invoke(GetInboxQuery $query): InboxView
    {
        $startedAt = microtime(true);

        try {
            $now = new DateTimeImmutable();
            $month = $now->format('Y-m');
            $expenses = ($this->expenseOverview)(new GetExpenseOverviewQuery($query->householdId, $month));
            $healthReview = ($this->healthReview)(new GetHealthReviewQuery($query->householdId));
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
                    sprintf('Review expense: %s', $this->displayTransactionTitle($expense->description)),
                    sprintf('%s · %.2f %s · %s', $expense->spentOn, $expense->amount, $expense->currency, $expense->reviewReason ?? 'Imported expense needs category check'),
                    'Review import',
                    '#expenses:import-review',
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
                    sprintf('Review income: %s', $this->displayTransactionTitle($entry->description)),
                    sprintf('%s · %.2f %s · %s', $entry->receivedOn, $entry->amount, $entry->currency, $entry->reviewReason ?? 'Imported income needs type check'),
                    'Review income',
                    '#expenses:import-review',
                    'expenses',
                    'import-review',
                    $entry->receivedOn,
                    null,
                    $entry->reviewStatus,
                );
            }

            foreach ($healthReview['items'] as $reviewItem) {
                $items[] = new InboxItemView(
                    sprintf('health-review-%s', $reviewItem->id),
                    'health',
                    $reviewItem->type,
                    $reviewItem->markerId ?? $reviewItem->labTestId ?? $reviewItem->id,
                    $reviewItem->severity,
                    null,
                    $reviewItem->title,
                    $reviewItem->detail,
                    'Review health',
                    '#health-review',
                    'health-review',
                    null,
                    $reviewItem->detectedAt,
                    null,
                    $reviewItem->type,
                );
            }

            foreach ($this->homeTasks->overdueTasks($query->householdId, $today, 20) as $task) {
                $items[] = $this->homeTaskItem($task, $task->priority() === HomeMaintenanceTask::PRIORITY_HIGH ? 'critical' : 'warning', 'Home task overdue', 'Mark done');
            }

            foreach ($this->homeTasks->upcomingTasks($query->householdId, $today, 14, 20) as $task) {
                $items[] = $this->homeTaskItem($task, 'info', 'Home task due soon', 'Open Home');
            }

            foreach ($this->reminders->overdueReminders($query->householdId, $today, 20) as $reminder) {
                $items[] = $this->reminderItem($reminder, $reminder->priority() === Reminder::PRIORITY_HIGH ? 'critical' : 'warning', 'Reminder overdue', 'Open reminders');
            }

            foreach ($this->reminders->dueTodayReminders($query->householdId, $today, 20) as $reminder) {
                $items[] = $this->reminderItem($reminder, 'warning', 'Reminder due today', 'Open reminders');
            }

            foreach ($this->documents->expiredDocuments($query->householdId, $today, 20) as $document) {
                $items[] = $this->documentItem($document, 'critical', 'Document expired');
            }

            foreach ($this->documents->expiringDocuments($query->householdId, $today, 30, 20) as $document) {
                $items[] = $this->documentItem($document, 'warning', 'Document expires soon');
            }

            usort($items, static function (InboxItemView $left, InboxItemView $right): int {
                $severityRank = ['critical' => 0, 'warning' => 1, 'info' => 2];
                $severityCompare = ($severityRank[$left->severity] ?? 9) <=> ($severityRank[$right->severity] ?? 9);

                if ($severityCompare !== 0) {
                    return $severityCompare;
                }

                return ($left->dueAt ?? $left->detectedAt) <=> ($right->dueAt ?? $right->detectedAt);
            });

            $view = new InboxView($items, [
                'total' => count($items),
                'critical' => count(array_filter($items, static fn (InboxItemView $item): bool => $item->severity === 'critical')),
                'warning' => count(array_filter($items, static fn (InboxItemView $item): bool => $item->severity === 'warning')),
                'info' => count(array_filter($items, static fn (InboxItemView $item): bool => $item->severity === 'info')),
                'highestSeverity' => $this->highestSeverity($items),
            ]);

            $this->logger->info('Inbox aggregation completed.', [
                'householdId' => $query->householdId,
                'durationMs' => round((microtime(true) - $startedAt) * 1000, 1),
                'itemCount' => count($items),
            ]);

            return $view;
        } catch (Throwable $exception) {
            $this->logger->error('Inbox aggregation failed.', [
                'householdId' => $query->householdId,
                'durationMs' => round((microtime(true) - $startedAt) * 1000, 1),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
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

    private function reminderItem(Reminder $reminder, string $severity, string $titlePrefix, string $targetAction): InboxItemView
    {
        return new InboxItemView(
            sprintf('reminder-%s', $reminder->id()),
            'reminders',
            'reminder',
            $reminder->id(),
            $severity,
            null,
            sprintf('%s: %s', $titlePrefix, $reminder->title()),
            $reminder->note() ?? sprintf('Due %s · priority %s.', $reminder->dueAt()->format('Y-m-d'), $reminder->priority()),
            $targetAction,
            '#reminders',
            'reminders',
            null,
            $reminder->createdAt()->format(DATE_ATOM),
            $reminder->dueAt()->format('Y-m-d'),
            $reminder->status(),
        );
    }

    private function documentItem(Document $document, string $severity, string $titlePrefix): InboxItemView
    {
        return new InboxItemView(
            sprintf('document-%s', $document->id()),
            'documents',
            'document_expiry',
            $document->id(),
            $severity,
            null,
            sprintf('%s: %s', $titlePrefix, $document->title()),
            sprintf('%s expires on %s.', $document->type(), $document->expiresAt()?->format('Y-m-d')),
            'Open documents',
            '#documents',
            'documents',
            null,
            $document->createdAt()->format(DATE_ATOM),
            $document->expiresAt()?->format('Y-m-d'),
            null,
        );
    }

    private function displayTransactionTitle(string $description): string
    {
        $title = preg_replace('/\s+/', ' ', trim($description)) ?? trim($description);
        $title = preg_replace('/^\d{4,}[-*\s]+/', '', $title) ?? $title;
        $title = preg_replace('/\s+\d+[,.]\d{2}\s+PLN\s+\d{4}-\d{2}-\d{2}.*/iu', '', $title) ?? $title;
        $title = preg_replace('/\s+Transakcja\s+(kartą|BLIK).*$/iu', '', $title) ?? $title;

        return mb_strlen($title) > 90 ? mb_substr($title, 0, 87).'...' : $title;
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
