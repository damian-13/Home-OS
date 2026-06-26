<?php

namespace App\Expenses\Infrastructure\Persistence;

use App\Expenses\Domain\Model\Expense;
use App\Expenses\Domain\Model\ExpenseBudget;
use App\Expenses\Domain\Model\ExpenseCategory;
use App\Expenses\Domain\Model\FinanceReviewRule;
use App\Expenses\Domain\Model\IncomeEntry;
use App\Expenses\Domain\Model\IncomeSource;
use App\Expenses\Domain\Model\RecurringBill;
use App\Expenses\Domain\Model\RecurringBillPayment;
use App\Expenses\Domain\Repository\ExpenseRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class DoctrineExpenseRepository implements ExpenseRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function saveCategory(ExpenseCategory $category): void
    {
        $this->entityManager->persist($category);
        $this->entityManager->flush();
    }

    public function saveExpense(Expense $expense): void
    {
        $this->entityManager->persist($expense);
        $this->entityManager->flush();
    }

    public function saveRecurringBill(RecurringBill $bill): void
    {
        $this->entityManager->persist($bill);
        $this->entityManager->flush();
    }

    public function saveIncomeSource(IncomeSource $source): void
    {
        $this->entityManager->persist($source);
        $this->entityManager->flush();
    }

    public function saveIncomeEntry(IncomeEntry $entry): void
    {
        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }

    public function saveBudget(ExpenseBudget $budget): void
    {
        $this->entityManager->persist($budget);
        $this->entityManager->flush();
    }

    public function saveRecurringBillPayment(RecurringBillPayment $payment): void
    {
        $this->entityManager->persist($payment);
        $this->entityManager->flush();
    }

    public function saveReviewRule(FinanceReviewRule $rule): void
    {
        $this->entityManager->persist($rule);
        $this->entityManager->flush();
    }

    public function getCategory(string $householdId, string $categoryId): ExpenseCategory
    {
        $category = $this->entityManager->getRepository(ExpenseCategory::class)->findOneBy([
            'id' => $categoryId,
            'householdId' => $householdId,
        ]);

        if (!$category instanceof ExpenseCategory) {
            throw new NotFoundHttpException(sprintf('Expense category "%s" was not found.', $categoryId));
        }

        return $category;
    }

    public function getExpense(string $householdId, string $expenseId): Expense
    {
        $expense = $this->entityManager->getRepository(Expense::class)->findOneBy([
            'id' => $expenseId,
            'householdId' => $householdId,
            'deletedAt' => null,
        ]);

        if (!$expense instanceof Expense) {
            throw new NotFoundHttpException(sprintf('Expense "%s" was not found.', $expenseId));
        }

        return $expense;
    }

    public function getRecurringBill(string $householdId, string $billId): RecurringBill
    {
        $bill = $this->entityManager->getRepository(RecurringBill::class)->findOneBy([
            'id' => $billId,
            'householdId' => $householdId,
            'deletedAt' => null,
        ]);

        if (!$bill instanceof RecurringBill) {
            throw new NotFoundHttpException(sprintf('Recurring bill "%s" was not found.', $billId));
        }

        return $bill;
    }

    public function getIncomeSource(string $householdId, string $sourceId): IncomeSource
    {
        $source = $this->entityManager->getRepository(IncomeSource::class)->findOneBy([
            'id' => $sourceId,
            'householdId' => $householdId,
            'deletedAt' => null,
        ]);

        if (!$source instanceof IncomeSource) {
            throw new NotFoundHttpException(sprintf('Income source "%s" was not found.', $sourceId));
        }

        return $source;
    }

    public function getIncomeEntry(string $householdId, string $entryId): IncomeEntry
    {
        $entry = $this->entityManager->getRepository(IncomeEntry::class)->findOneBy([
            'id' => $entryId,
            'householdId' => $householdId,
            'deletedAt' => null,
        ]);

        if (!$entry instanceof IncomeEntry) {
            throw new NotFoundHttpException(sprintf('Income entry "%s" was not found.', $entryId));
        }

        return $entry;
    }

    public function findBudget(string $householdId, string $categoryId, string $month): ?ExpenseBudget
    {
        return $this->entityManager->getRepository(ExpenseBudget::class)
            ->createQueryBuilder('budget')
            ->join('budget.category', 'category')
            ->andWhere('budget.householdId = :householdId')
            ->andWhere('category.id = :categoryId')
            ->andWhere('budget.month = :month')
            ->setParameter('householdId', $householdId)
            ->setParameter('categoryId', $categoryId)
            ->setParameter('month', $month)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findRecurringBillPayment(string $householdId, string $billId, string $month): ?RecurringBillPayment
    {
        return $this->entityManager->getRepository(RecurringBillPayment::class)->findOneBy([
            'householdId' => $householdId,
            'billId' => $billId,
            'month' => $month,
        ]);
    }

    public function categoriesForHousehold(string $householdId): array
    {
        return $this->entityManager->getRepository(ExpenseCategory::class)->findBy(
            ['householdId' => $householdId],
            ['name' => 'ASC'],
        );
    }

    public function latestExpenses(string $householdId, ?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null, ?string $categoryId = null, ?string $paidByMemberId = null, int $limit = 20): array
    {
        $builder = $this->entityManager->getRepository(Expense::class)
            ->createQueryBuilder('expense')
            ->andWhere('expense.householdId = :householdId')
            ->andWhere('expense.deletedAt IS NULL')
            ->setParameter('householdId', $householdId)
            ->orderBy('expense.spentOn', 'DESC')
            ->addOrderBy('expense.id', 'DESC')
            ->setMaxResults($limit);

        if ($from) {
            $builder
                ->andWhere('expense.spentOn >= :latestFrom')
                ->setParameter('latestFrom', $from);
        }

        if ($to) {
            $builder
                ->andWhere('expense.spentOn <= :latestTo')
                ->setParameter('latestTo', $to);
        }

        $this->applyOptionalFilters($builder, $categoryId, $paidByMemberId);

        return $builder->getQuery()->getResult();
    }

    public function expensesBetween(string $householdId, DateTimeImmutable $from, DateTimeImmutable $to, ?string $categoryId = null, ?string $paidByMemberId = null): array
    {
        $builder = $this->entityManager->getRepository(Expense::class)
            ->createQueryBuilder('expense')
            ->andWhere('expense.householdId = :householdId')
            ->andWhere('expense.deletedAt IS NULL')
            ->andWhere('expense.spentOn >= :from')
            ->andWhere('expense.spentOn <= :to')
            ->setParameter('householdId', $householdId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('expense.spentOn', 'DESC');

        $this->applyOptionalFilters($builder, $categoryId, $paidByMemberId);

        return $builder->getQuery()->getResult();
    }

    public function recurringBillsForHousehold(string $householdId, ?string $categoryId = null, ?string $paidByMemberId = null): array
    {
        $builder = $this->entityManager->getRepository(RecurringBill::class)
            ->createQueryBuilder('bill')
            ->andWhere('bill.householdId = :householdId')
            ->andWhere('bill.active = true')
            ->andWhere('bill.deletedAt IS NULL')
            ->setParameter('householdId', $householdId)
            ->orderBy('bill.dueDay', 'ASC')
            ->addOrderBy('bill.name', 'ASC');

        if ($categoryId) {
            $builder
                ->andWhere('bill.category = :billCategoryId')
                ->setParameter('billCategoryId', $categoryId);
        }

        if ($paidByMemberId) {
            $builder
                ->andWhere('bill.paidByMemberId = :billPaidByMemberId')
                ->setParameter('billPaidByMemberId', $paidByMemberId);
        }

        return $builder->getQuery()->getResult();
    }

    public function incomeSourcesForHousehold(string $householdId): array
    {
        return $this->entityManager->getRepository(IncomeSource::class)
            ->createQueryBuilder('source')
            ->andWhere('source.householdId = :householdId')
            ->andWhere('source.deletedAt IS NULL')
            ->setParameter('householdId', $householdId)
            ->orderBy('source.active', 'DESC')
            ->addOrderBy('source.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function incomeEntriesBetween(string $householdId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return $this->entityManager->getRepository(IncomeEntry::class)
            ->createQueryBuilder('entry')
            ->andWhere('entry.householdId = :householdId')
            ->andWhere('entry.deletedAt IS NULL')
            ->andWhere('entry.receivedOn >= :from')
            ->andWhere('entry.receivedOn <= :to')
            ->setParameter('householdId', $householdId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('entry.receivedOn', 'DESC')
            ->addOrderBy('entry.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function budgetsForMonth(string $householdId, string $month): array
    {
        return $this->entityManager->getRepository(ExpenseBudget::class)->findBy([
            'householdId' => $householdId,
            'month' => $month,
        ]);
    }

    public function recurringBillPaymentsForMonth(string $householdId, string $month): array
    {
        return $this->entityManager->getRepository(RecurringBillPayment::class)->findBy([
            'householdId' => $householdId,
            'month' => $month,
        ]);
    }

    public function reviewRulesForHousehold(string $householdId): array
    {
        return $this->entityManager->getRepository(FinanceReviewRule::class)
            ->createQueryBuilder('rule')
            ->andWhere('rule.householdId = :householdId')
            ->andWhere('rule.active = true')
            ->setParameter('householdId', $householdId)
            ->orderBy('rule.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    private function applyOptionalFilters(\Doctrine\ORM\QueryBuilder $builder, ?string $categoryId, ?string $paidByMemberId): void
    {
        if ($categoryId) {
            $builder
                ->andWhere('expense.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        if ($paidByMemberId) {
            $builder
                ->andWhere('expense.paidByMemberId = :paidByMemberId')
                ->setParameter('paidByMemberId', $paidByMemberId);
        }
    }
}
