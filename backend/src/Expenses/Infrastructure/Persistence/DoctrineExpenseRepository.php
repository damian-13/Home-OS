<?php

namespace App\Expenses\Infrastructure\Persistence;

use App\Expenses\Domain\Model\Expense;
use App\Expenses\Domain\Model\ExpenseCategory;
use App\Expenses\Domain\Model\RecurringBill;
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
