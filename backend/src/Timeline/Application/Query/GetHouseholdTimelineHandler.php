<?php

namespace App\Timeline\Application\Query;

use App\Documents\Domain\Model\Document;
use App\Expenses\Domain\Model\Expense;
use App\Expenses\Domain\Model\IncomeEntry;
use App\Expenses\Domain\Model\RecurringBillPayment;
use App\Health\Domain\Model\BloodTest;
use App\Health\Domain\Model\BloodTestMarker;
use App\Home\Domain\Model\HomeMaintenanceTask;
use App\Reminders\Domain\Model\Reminder;
use App\Shared\Application\Query\QueryHandler;
use App\Timeline\Application\Dto\TimelineItemView;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class GetHouseholdTimelineHandler implements QueryHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function handles(): string
    {
        return GetHouseholdTimelineQuery::class;
    }

    /**
     * @return array{items: list<TimelineItemView>, grouped: array<string, int>}
     */
    public function __invoke(GetHouseholdTimelineQuery $query): array
    {
        $items = [
            ...$this->expenseEvents($query->householdId),
            ...$this->incomeEvents($query->householdId),
            ...$this->healthEvents($query->householdId),
            ...$this->homeEvents($query->householdId),
            ...$this->reminderEvents($query->householdId),
            ...$this->documentEvents($query->householdId),
            ...$this->recurringBillPaymentEvents($query->householdId),
        ];

        usort($items, static fn (array $a, array $b): int => $b['time'] <=> $a['time']);

        $timeline = array_map(static fn (array $row): TimelineItemView => $row['item'], array_slice($items, 0, 80));
        $grouped = [];

        foreach ($timeline as $item) {
            $grouped[$item->sourceModule] = ($grouped[$item->sourceModule] ?? 0) + 1;
        }

        return ['items' => $timeline, 'grouped' => $grouped];
    }

    /**
     * @return list<array{time: int, item: TimelineItemView}>
     */
    private function expenseEvents(string $householdId): array
    {
        $expenses = $this->entityManager->getRepository(Expense::class)
            ->createQueryBuilder('expense')
            ->join('expense.category', 'category')
            ->andWhere('expense.householdId = :householdId')
            ->andWhere('expense.deletedAt IS NULL')
            ->setParameter('householdId', $householdId)
            ->orderBy('expense.spentOn', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        $dailySpending = [];
        $events = [];

        foreach ($expenses as $expense) {
            if ($expense->amountCents() >= 100000 || $expense->reviewStatus() === 'needs_review') {
                $events[] = $this->row($expense->spentOn(), new TimelineItemView(
                    sprintf('expense-spent-%s', $expense->id()),
                    'expenses',
                    'expense',
                    $expense->id(),
                    'expense_spent',
                    $expense->reviewStatus() === 'needs_review' ? sprintf('Review expense: %s', $expense->description()) : $expense->description(),
                    sprintf('%s · %.2f %s', $expense->category()->name(), $expense->amountCents() / 100, $expense->currency()),
                    $expense->spentOn()->format('Y-m-d'),
                    '#expenses',
                    $expense->amountCents() >= 100000 ? 'high' : 'normal',
                ));

                continue;
            }

            $day = $expense->spentOn()->format('Y-m-d');
            $dailySpending[$day] ??= ['date' => $expense->spentOn(), 'count' => 0, 'amount' => 0, 'currency' => $expense->currency()];
            $dailySpending[$day]['count']++;
            $dailySpending[$day]['amount'] += $expense->amountCents();
        }

        foreach ($dailySpending as $day => $summary) {
            $events[] = $this->row($summary['date'], new TimelineItemView(
                sprintf('expense-daily-summary-%s', $day),
                'expenses',
                'expense_summary',
                $day,
                'daily_spending_summary',
                sprintf('Daily spending: %d transaction%s', $summary['count'], $summary['count'] === 1 ? '' : 's'),
                sprintf('%.2f %s total', $summary['amount'] / 100, $summary['currency']),
                $day,
                '#expenses',
                'normal',
            ));
        }

        return $events;
    }

    /**
     * @return list<array{time: int, item: TimelineItemView}>
     */
    private function incomeEvents(string $householdId): array
    {
        $entries = $this->entityManager->getRepository(IncomeEntry::class)
            ->createQueryBuilder('entry')
            ->andWhere('entry.householdId = :householdId')
            ->andWhere('entry.deletedAt IS NULL')
            ->setParameter('householdId', $householdId)
            ->orderBy('entry.receivedOn', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        return array_map(fn (IncomeEntry $entry): array => $this->row($entry->receivedOn(), new TimelineItemView(
            sprintf('income-received-%s', $entry->id()),
            'expenses',
            'income',
            $entry->id(),
            'income_received',
            $entry->description(),
            sprintf('%s · %.2f %s', $entry->incomeKind(), $entry->amountCents() / 100, $entry->currency()),
            $entry->receivedOn()->format('Y-m-d'),
            '#expenses',
            'normal',
        )), $entries);
    }

    /**
     * @return list<array{time: int, item: TimelineItemView}>
     */
    private function healthEvents(string $householdId): array
    {
        $tests = $this->entityManager->getRepository(BloodTest::class)
            ->createQueryBuilder('bloodTest')
            ->andWhere('bloodTest.householdId = :householdId')
            ->andWhere('bloodTest.deletedAt IS NULL')
            ->setParameter('householdId', $householdId)
            ->orderBy('bloodTest.testedAt', 'DESC')
            ->setMaxResults(15)
            ->getQuery()
            ->getResult();

        $markers = $this->entityManager->getRepository(BloodTestMarker::class)
            ->createQueryBuilder('marker')
            ->join('marker.bloodTest', 'bloodTest')
            ->andWhere('bloodTest.householdId = :householdId')
            ->andWhere('bloodTest.deletedAt IS NULL')
            ->andWhere('marker.status IN (:statuses)')
            ->setParameter('householdId', $householdId)
            ->setParameter('statuses', ['low', 'high'])
            ->orderBy('bloodTest.testedAt', 'DESC')
            ->setMaxResults(15)
            ->getQuery()
            ->getResult();

        $events = array_map(fn (BloodTest $test): array => $this->row($test->testedAt(), new TimelineItemView(
            sprintf('blood-test-%s', $test->id()),
            'health',
            'blood_test',
            $test->id(),
            'blood_test_added',
            $test->labName() ? sprintf('Blood test from %s', $test->labName()) : 'Blood test added',
            sprintf('%d markers recorded.', count($test->markers())),
            $test->testedAt()->format('Y-m-d'),
            '#health',
            'normal',
        )), $tests);

        foreach ($markers as $marker) {
            $events[] = $this->row($marker->bloodTest()->testedAt(), new TimelineItemView(
                sprintf('blood-marker-%s', $marker->id()),
                'health',
                'blood_marker',
                $marker->bloodTest()->id(),
                'abnormal_marker',
                sprintf('%s was %s', $marker->name(), $marker->status()),
                sprintf('%s %s on %s', $marker->value(), $marker->unit(), $marker->bloodTest()->testedAt()->format('Y-m-d')),
                $marker->bloodTest()->testedAt()->format('Y-m-d'),
                '#health',
                'high',
            ));
        }

        return $events;
    }

    /**
     * @return list<array{time: int, item: TimelineItemView}>
     */
    private function homeEvents(string $householdId): array
    {
        $tasks = $this->entityManager->getRepository(HomeMaintenanceTask::class)
            ->createQueryBuilder('task')
            ->andWhere('task.householdId = :householdId')
            ->andWhere('task.deletedAt IS NULL')
            ->setParameter('householdId', $householdId)
            ->orderBy('task.nextDueAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        $events = [];

        foreach ($tasks as $task) {
            $date = $task->completedAt() ?? $task->nextDueAt();
            $isCompleted = $task->completedAt() !== null;
            $events[] = $this->row($date, new TimelineItemView(
                sprintf('home-task-%s-%s', $isCompleted ? 'completed' : 'due', $task->id()),
                'home',
                'maintenance_task',
                $task->id(),
                $isCompleted ? 'home_task_completed' : 'home_task_due',
                $isCompleted ? sprintf('Completed: %s', $task->title()) : sprintf('Home task due: %s', $task->title()),
                sprintf('%s · %s priority', $task->area(), $task->priority()),
                $this->formatDate($date),
                '#home',
                $task->priority() === HomeMaintenanceTask::PRIORITY_HIGH ? 'high' : 'normal',
            ));
        }

        return $events;
    }

    /**
     * @return list<array{time: int, item: TimelineItemView}>
     */
    private function reminderEvents(string $householdId): array
    {
        $reminders = $this->entityManager->getRepository(Reminder::class)
            ->createQueryBuilder('reminder')
            ->andWhere('reminder.householdId = :householdId')
            ->andWhere('reminder.deletedAt IS NULL')
            ->setParameter('householdId', $householdId)
            ->orderBy('reminder.dueAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        $events = [];

        foreach ($reminders as $reminder) {
            $date = $reminder->completedAt() ?? $reminder->skippedAt() ?? $reminder->dueAt();
            $eventType = $reminder->completedAt() ? 'reminder_completed' : ($reminder->skippedAt() ? 'reminder_skipped' : 'reminder_due');
            $events[] = $this->row($date, new TimelineItemView(
                sprintf('%s-%s', $eventType, $reminder->id()),
                'reminders',
                'reminder',
                $reminder->id(),
                $eventType,
                $reminder->title(),
                sprintf('%s · %s priority', $reminder->status(), $reminder->priority()),
                $this->formatDate($date),
                '#reminders',
                $reminder->priority() === Reminder::PRIORITY_HIGH ? 'high' : 'normal',
            ));
        }

        return $events;
    }

    /**
     * @return list<array{time: int, item: TimelineItemView}>
     */
    private function documentEvents(string $householdId): array
    {
        $documents = $this->entityManager->getRepository(Document::class)
            ->createQueryBuilder('document')
            ->andWhere('document.householdId = :householdId')
            ->andWhere('document.deletedAt IS NULL')
            ->setParameter('householdId', $householdId)
            ->orderBy('document.createdAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        $events = [];

        foreach ($documents as $document) {
            $events[] = $this->row($document->createdAt(), new TimelineItemView(
                sprintf('document-created-%s', $document->id()),
                'documents',
                'document',
                $document->id(),
                'document_added',
                sprintf('Document added: %s', $document->title()),
                sprintf('%s%s', $document->type(), $document->originalName() ? sprintf(' · %s', $document->originalName()) : ''),
                $document->createdAt()->format(DATE_ATOM),
                '#documents',
                'normal',
            ));

            if ($document->expiresAt()) {
                $events[] = $this->row($document->expiresAt(), new TimelineItemView(
                    sprintf('document-expiry-%s', $document->id()),
                    'documents',
                    'document',
                    $document->id(),
                    'document_expires',
                    sprintf('Document expires: %s', $document->title()),
                    sprintf('%s expires on %s', $document->type(), $document->expiresAt()->format('Y-m-d')),
                    $document->expiresAt()->format('Y-m-d'),
                    '#documents',
                    $document->expiresAt() < new DateTimeImmutable('today') ? 'high' : 'normal',
                ));
            }
        }

        return $events;
    }

    /**
     * @return list<array{time: int, item: TimelineItemView}>
     */
    private function recurringBillPaymentEvents(string $householdId): array
    {
        $payments = $this->entityManager->getRepository(RecurringBillPayment::class)
            ->createQueryBuilder('payment')
            ->andWhere('payment.householdId = :householdId')
            ->andWhere('payment.status = :status')
            ->andWhere('payment.paidOn IS NOT NULL')
            ->setParameter('householdId', $householdId)
            ->setParameter('status', 'paid')
            ->orderBy('payment.paidOn', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $events = [];

        foreach ($payments as $payment) {
            if (!$payment->paidOn()) {
                continue;
            }

            $events[] = $this->row($payment->paidOn(), new TimelineItemView(
                sprintf('recurring-bill-paid-%s', $payment->id()),
                'expenses',
                'recurring_bill_payment',
                $payment->billId(),
                'recurring_bill_paid',
                'Recurring bill paid',
                sprintf('%s%s', $payment->month(), $payment->amountOverrideCents() ? sprintf(' · %.2f PLN', $payment->amountOverrideCents() / 100) : ''),
                $payment->paidOn()->format('Y-m-d'),
                '#expenses',
                'normal',
            ));
        }

        return $events;
    }

    /**
     * @return array{time: int, item: TimelineItemView}
     */
    private function row(DateTimeInterface $date, TimelineItemView $item): array
    {
        return ['time' => $date->getTimestamp(), 'item' => $item];
    }

    private function formatDate(DateTimeInterface $date): string
    {
        return $date instanceof DateTimeImmutable && $date->format('H:i:s') === '00:00:00'
            ? $date->format('Y-m-d')
            : $date->format(DATE_ATOM);
    }
}
