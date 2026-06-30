<?php

namespace App\Notifications\Application\Service;

use App\Documents\Domain\Model\Document;
use App\Documents\Domain\Repository\DocumentRepository;
use App\Expenses\Application\Dto\ExpenseView;
use App\Expenses\Application\Dto\IncomeEntryView;
use App\Expenses\Application\Query\GetExpenseOverviewHandler;
use App\Expenses\Application\Query\GetExpenseOverviewQuery;
use App\Health\Application\Dto\HealthReviewItemView;
use App\Health\Application\Query\GetHealthReviewHandler;
use App\Health\Application\Query\GetHealthReviewQuery;
use App\Home\Domain\Model\HomeMaintenanceTask;
use App\Home\Domain\Repository\HomeMaintenanceRepository;
use App\Notifications\Application\Dto\NotificationDigestItemView;
use App\Notifications\Application\Dto\NotificationDigestSectionView;
use App\Notifications\Application\Dto\NotificationDigestView;
use App\Reminders\Domain\Model\Reminder;
use App\Reminders\Domain\Repository\ReminderRepository;
use DateTimeImmutable;

final readonly class DailyNotificationDigestBuilder
{
    public function __construct(
        private ReminderRepository $reminders,
        private HomeMaintenanceRepository $homeTasks,
        private DocumentRepository $documents,
        private GetExpenseOverviewHandler $expenseOverview,
        private GetHealthReviewHandler $healthReview,
    ) {
    }

    public function build(string $householdId, ?DateTimeImmutable $now = null): NotificationDigestView
    {
        $now ??= new DateTimeImmutable();
        $today = new DateTimeImmutable($now->format('Y-m-d'));
        $month = $now->format('Y-m');
        $expenses = ($this->expenseOverview)(new GetExpenseOverviewQuery($householdId, $month));
        $healthReview = ($this->healthReview)(new GetHealthReviewQuery($householdId));

        $sections = [
            $this->section(
                'overdue_reminders',
                'Overdue reminders',
                array_map(
                    fn (Reminder $reminder): NotificationDigestItemView => $this->reminderItem($reminder, 'critical'),
                    $this->reminders->overdueReminders($householdId, $today, 20),
                ),
            ),
            $this->section(
                'today_reminders',
                'Reminders due today',
                array_map(
                    fn (Reminder $reminder): NotificationDigestItemView => $this->reminderItem($reminder, 'warning'),
                    $this->reminders->dueTodayReminders($householdId, $today, 20),
                ),
            ),
            $this->section(
                'overdue_home_tasks',
                'Overdue home maintenance',
                array_map(
                    fn (HomeMaintenanceTask $task): NotificationDigestItemView => $this->homeTaskItem($task),
                    $this->homeTasks->overdueTasks($householdId, $today, 20),
                ),
            ),
            $this->section(
                'expiring_documents',
                'Documents expiring soon',
                array_map(
                    fn (Document $document): NotificationDigestItemView => $this->documentItem($document, $today),
                    $this->documents->expiringDocuments($householdId, $today, 30, 20),
                ),
            ),
            $this->section(
                'finance_review',
                'Imported finance rows needing review',
                $this->financeReviewItems($expenses->review),
            ),
            $this->section(
                'health_review',
                'High-severity health review',
                array_map(
                    fn (HealthReviewItemView $item): NotificationDigestItemView => $this->healthReviewItem($item),
                    array_values(array_filter(
                        $healthReview['items'],
                        static fn (HealthReviewItemView $item): bool => $item->severity === 'critical',
                    )),
                ),
            ),
        ];

        $counts = [];
        $totalItems = 0;
        foreach ($sections as $section) {
            $counts[$section->key] = $section->count;
            $totalItems += $section->count;
        }

        return new NotificationDigestView(
            $householdId,
            $now->format(DATE_ATOM),
            $sections,
            $counts,
            $totalItems,
        );
    }

    /**
     * @param list<NotificationDigestItemView> $items
     */
    private function section(string $key, string $title, array $items): NotificationDigestSectionView
    {
        return new NotificationDigestSectionView($key, $title, count($items), $items);
    }

    private function reminderItem(Reminder $reminder, string $severity): NotificationDigestItemView
    {
        return new NotificationDigestItemView(
            sprintf('reminder-%s', $reminder->id()),
            $reminder->title(),
            $reminder->note() ?? sprintf('Due %s · priority %s.', $reminder->dueAt()->format('Y-m-d'), $reminder->priority()),
            $severity,
            '#reminders',
            $reminder->dueAt()->format('Y-m-d'),
        );
    }

    private function homeTaskItem(HomeMaintenanceTask $task): NotificationDigestItemView
    {
        return new NotificationDigestItemView(
            sprintf('home-task-%s', $task->id()),
            $task->title(),
            sprintf('%s · due %s · priority %s.', $task->area(), $task->nextDueAt()->format('Y-m-d'), $task->priority()),
            $task->priority() === HomeMaintenanceTask::PRIORITY_HIGH ? 'critical' : 'warning',
            '#home',
            $task->nextDueAt()->format('Y-m-d'),
        );
    }

    private function documentItem(Document $document, DateTimeImmutable $today): NotificationDigestItemView
    {
        $expiresAt = $document->expiresAt();
        $days = $expiresAt === null ? null : (int) $today->diff($expiresAt)->format('%r%a');

        return new NotificationDigestItemView(
            sprintf('document-%s', $document->id()),
            $document->title(),
            sprintf('%s expires on %s%s.', $document->type(), $expiresAt?->format('Y-m-d'), $days === null ? '' : sprintf(' (%d days)', $days)),
            $days !== null && $days <= 7 ? 'warning' : 'info',
            '#documents',
            $expiresAt?->format('Y-m-d'),
        );
    }

    /**
     * @param array<string, mixed> $review
     * @return list<NotificationDigestItemView>
     */
    private function financeReviewItems(array $review): array
    {
        $items = [];

        foreach (($review['expenseCandidates'] ?? []) as $expense) {
            if (!$expense instanceof ExpenseView) {
                continue;
            }

            $items[] = new NotificationDigestItemView(
                sprintf('finance-expense-%s', $expense->id),
                $this->displayTransactionTitle($expense->description),
                sprintf('%s · %.2f %s · %s', $expense->spentOn, $expense->amount, $expense->currency, $expense->reviewReason ?? 'Imported expense needs review'),
                'warning',
                '#expenses:import-review',
                null,
            );
        }

        foreach (($review['incomeCandidates'] ?? []) as $entry) {
            if (!$entry instanceof IncomeEntryView) {
                continue;
            }

            $items[] = new NotificationDigestItemView(
                sprintf('finance-income-%s', $entry->id),
                $this->displayTransactionTitle($entry->description),
                sprintf('%s · %.2f %s · %s', $entry->receivedOn, $entry->amount, $entry->currency, $entry->reviewReason ?? 'Imported income needs review'),
                'warning',
                '#expenses:import-review',
                null,
            );
        }

        return $items;
    }

    private function healthReviewItem(HealthReviewItemView $item): NotificationDigestItemView
    {
        return new NotificationDigestItemView(
            sprintf('health-review-%s', $item->id),
            $item->title,
            $item->detail,
            $item->severity,
            '#health-review',
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
}
