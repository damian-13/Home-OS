<?php

namespace App\Search\Application\Query;

use App\Documents\Domain\Model\Document;
use App\Expenses\Domain\Model\Expense;
use App\Expenses\Domain\Model\IncomeEntry;
use App\Health\Domain\Model\BloodTest;
use App\Health\Domain\Model\BloodTestMarker;
use App\Home\Domain\Model\HomeMaintenanceTask;
use App\Reminders\Domain\Model\Reminder;
use App\Search\Application\Dto\SearchResultView;
use App\Shared\Application\Query\QueryHandler;
use Doctrine\ORM\EntityManagerInterface;

final readonly class SearchHouseholdHandler implements QueryHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function handles(): string
    {
        return SearchHouseholdQuery::class;
    }

    /**
     * @return array{query: string, results: list<SearchResultView>, grouped: array<string, int>}
     */
    public function __invoke(SearchHouseholdQuery $query): array
    {
        $term = trim($query->query);

        if (strlen($term) < 2) {
            return ['query' => $term, 'results' => [], 'grouped' => []];
        }

        $like = '%'.strtolower($term).'%';
        $results = [
            ...$this->searchExpenses($query->householdId, $like, $term),
            ...$this->searchIncome($query->householdId, $like, $term),
            ...$this->searchHealth($query->householdId, $like, $term),
            ...$this->searchHomeTasks($query->householdId, $like, $term),
            ...$this->searchReminders($query->householdId, $like, $term),
            ...$this->searchDocuments($query->householdId, $like, $term),
        ];

        usort($results, static fn (SearchResultView $a, SearchResultView $b): int => $b->relevance <=> $a->relevance ?: strcmp((string) $b->date, (string) $a->date));
        $results = array_slice($results, 0, 40);
        $grouped = [];

        foreach ($results as $result) {
            $grouped[$result->sourceModule] = ($grouped[$result->sourceModule] ?? 0) + 1;
        }

        return ['query' => $term, 'results' => $results, 'grouped' => $grouped];
    }

    /**
     * @return list<SearchResultView>
     */
    private function searchExpenses(string $householdId, string $like, string $term): array
    {
        $expenses = $this->entityManager->getRepository(Expense::class)
            ->createQueryBuilder('expense')
            ->join('expense.category', 'category')
            ->andWhere('expense.householdId = :householdId')
            ->andWhere('expense.deletedAt IS NULL')
            ->andWhere('LOWER(expense.description) LIKE :term OR LOWER(category.name) LIKE :term')
            ->setParameter('householdId', $householdId)
            ->setParameter('term', $like)
            ->orderBy('expense.spentOn', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return array_map(fn (Expense $expense): SearchResultView => new SearchResultView(
            sprintf('expense-%s', $expense->id()),
            'expenses',
            'expense',
            $expense->id(),
            $expense->description(),
            sprintf('%s · %.2f %s · %s', $expense->category()->name(), $expense->amountCents() / 100, $expense->currency(), $expense->spentOn()->format('Y-m-d')),
            $expense->spentOn()->format('Y-m-d'),
            '#expenses',
            $this->score($term, $expense->description(), $expense->category()->name()),
        ), $expenses);
    }

    /**
     * @return list<SearchResultView>
     */
    private function searchIncome(string $householdId, string $like, string $term): array
    {
        $entries = $this->entityManager->getRepository(IncomeEntry::class)
            ->createQueryBuilder('entry')
            ->andWhere('entry.householdId = :householdId')
            ->andWhere('entry.deletedAt IS NULL')
            ->andWhere('LOWER(entry.description) LIKE :term OR LOWER(entry.incomeKind) LIKE :term')
            ->setParameter('householdId', $householdId)
            ->setParameter('term', $like)
            ->orderBy('entry.receivedOn', 'DESC')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();

        return array_map(fn (IncomeEntry $entry): SearchResultView => new SearchResultView(
            sprintf('income-%s', $entry->id()),
            'expenses',
            'income',
            $entry->id(),
            $entry->description(),
            sprintf('%s income · %.2f %s · %s', $entry->incomeKind(), $entry->amountCents() / 100, $entry->currency(), $entry->receivedOn()->format('Y-m-d')),
            $entry->receivedOn()->format('Y-m-d'),
            '#expenses',
            $this->score($term, $entry->description(), $entry->incomeKind()),
        ), $entries);
    }

    /**
     * @return list<SearchResultView>
     */
    private function searchHealth(string $householdId, string $like, string $term): array
    {
        $tests = $this->entityManager->getRepository(BloodTest::class)
            ->createQueryBuilder('bloodTest')
            ->andWhere('bloodTest.householdId = :householdId')
            ->andWhere('bloodTest.deletedAt IS NULL')
            ->andWhere('LOWER(COALESCE(bloodTest.labName, \'\')) LIKE :term OR LOWER(COALESCE(bloodTest.notes, \'\')) LIKE :term')
            ->setParameter('householdId', $householdId)
            ->setParameter('term', $like)
            ->orderBy('bloodTest.testedAt', 'DESC')
            ->setMaxResults(6)
            ->getQuery()
            ->getResult();

        $markers = $this->entityManager->getRepository(BloodTestMarker::class)
            ->createQueryBuilder('marker')
            ->join('marker.bloodTest', 'bloodTest')
            ->andWhere('bloodTest.householdId = :householdId')
            ->andWhere('bloodTest.deletedAt IS NULL')
            ->andWhere('LOWER(marker.name) LIKE :term OR LOWER(marker.unit) LIKE :term OR LOWER(COALESCE(marker.notes, \'\')) LIKE :term')
            ->setParameter('householdId', $householdId)
            ->setParameter('term', $like)
            ->orderBy('bloodTest.testedAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $results = array_map(fn (BloodTest $test): SearchResultView => new SearchResultView(
            sprintf('blood-test-%s', $test->id()),
            'health',
            'blood_test',
            $test->id(),
            $test->labName() ? sprintf('Blood test from %s', $test->labName()) : 'Blood test',
            sprintf('%d markers · %s', count($test->markers()), $test->testedAt()->format('Y-m-d')),
            $test->testedAt()->format('Y-m-d'),
            '#health',
            $this->score($term, $test->labName() ?? '', $test->notes() ?? ''),
        ), $tests);

        foreach ($markers as $marker) {
            $results[] = new SearchResultView(
                sprintf('health-marker-%s', $marker->id()),
                'health',
                'blood_marker',
                $marker->bloodTest()->id(),
                $marker->name(),
                sprintf('%s %s · %s · %s', $marker->value(), $marker->unit(), $marker->status(), $marker->bloodTest()->testedAt()->format('Y-m-d')),
                $marker->bloodTest()->testedAt()->format('Y-m-d'),
                '#health',
                $this->score($term, $marker->name(), $marker->unit(), $marker->notes() ?? ''),
            );
        }

        return $results;
    }

    /**
     * @return list<SearchResultView>
     */
    private function searchHomeTasks(string $householdId, string $like, string $term): array
    {
        $tasks = $this->entityManager->getRepository(HomeMaintenanceTask::class)
            ->createQueryBuilder('task')
            ->andWhere('task.householdId = :householdId')
            ->andWhere('task.deletedAt IS NULL')
            ->andWhere('LOWER(task.title) LIKE :term OR LOWER(task.area) LIKE :term OR LOWER(COALESCE(task.notes, \'\')) LIKE :term')
            ->setParameter('householdId', $householdId)
            ->setParameter('term', $like)
            ->orderBy('task.nextDueAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return array_map(fn (HomeMaintenanceTask $task): SearchResultView => new SearchResultView(
            sprintf('home-task-%s', $task->id()),
            'home',
            'maintenance_task',
            $task->id(),
            $task->title(),
            sprintf('%s · %s · due %s', $task->area(), $task->status(), $task->nextDueAt()->format('Y-m-d')),
            $task->nextDueAt()->format('Y-m-d'),
            '#home',
            $this->score($term, $task->title(), $task->area(), $task->notes() ?? ''),
        ), $tasks);
    }

    /**
     * @return list<SearchResultView>
     */
    private function searchReminders(string $householdId, string $like, string $term): array
    {
        $reminders = $this->entityManager->getRepository(Reminder::class)
            ->createQueryBuilder('reminder')
            ->andWhere('reminder.householdId = :householdId')
            ->andWhere('reminder.deletedAt IS NULL')
            ->andWhere('LOWER(reminder.title) LIKE :term OR LOWER(COALESCE(reminder.note, \'\')) LIKE :term OR LOWER(COALESCE(reminder.relatedType, \'\')) LIKE :term')
            ->setParameter('householdId', $householdId)
            ->setParameter('term', $like)
            ->orderBy('reminder.dueAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return array_map(fn (Reminder $reminder): SearchResultView => new SearchResultView(
            sprintf('reminder-%s', $reminder->id()),
            'reminders',
            'reminder',
            $reminder->id(),
            $reminder->title(),
            sprintf('%s · due %s', $reminder->status(), $reminder->dueAt()->format('Y-m-d')),
            $reminder->dueAt()->format('Y-m-d'),
            '#reminders',
            $this->score($term, $reminder->title(), $reminder->note() ?? '', $reminder->relatedType() ?? ''),
        ), $reminders);
    }

    /**
     * @return list<SearchResultView>
     */
    private function searchDocuments(string $householdId, string $like, string $term): array
    {
        $documents = $this->entityManager->getRepository(Document::class)
            ->createQueryBuilder('document')
            ->andWhere('document.householdId = :householdId')
            ->andWhere('document.deletedAt IS NULL')
            ->andWhere('LOWER(document.title) LIKE :term OR LOWER(document.type) LIKE :term OR LOWER(COALESCE(document.tags, \'\')) LIKE :term OR LOWER(COALESCE(document.note, \'\')) LIKE :term OR LOWER(COALESCE(document.originalName, \'\')) LIKE :term')
            ->setParameter('householdId', $householdId)
            ->setParameter('term', $like)
            ->orderBy('document.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return array_map(fn (Document $document): SearchResultView => new SearchResultView(
            sprintf('document-%s', $document->id()),
            'documents',
            'document',
            $document->id(),
            $document->title(),
            sprintf('%s%s', $document->type(), $document->expiresAt() ? sprintf(' · expires %s', $document->expiresAt()->format('Y-m-d')) : ''),
            $document->expiresAt()?->format('Y-m-d') ?? $document->createdAt()->format('Y-m-d'),
            '#documents',
            $this->score($term, $document->title(), $document->type(), $document->tags() ?? '', $document->note() ?? '', $document->originalName() ?? ''),
        ), $documents);
    }

    private function score(string $term, string ...$fields): int
    {
        $needle = strtolower(trim($term));
        $score = 10;

        foreach ($fields as $field) {
            $value = strtolower(trim($field));

            if ($value === $needle) {
                $score = max($score, 100);
            } elseif (str_starts_with($value, $needle)) {
                $score = max($score, 80);
            } elseif (str_contains($value, $needle)) {
                $score = max($score, 50);
            }
        }

        return $score;
    }
}
