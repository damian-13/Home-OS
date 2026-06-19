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

    public function categoriesForHousehold(string $householdId): array
    {
        return $this->entityManager->getRepository(ExpenseCategory::class)->findBy(
            ['householdId' => $householdId],
            ['name' => 'ASC'],
        );
    }

    public function latestExpenses(string $householdId, int $limit = 20): array
    {
        return $this->entityManager->getRepository(Expense::class)
            ->createQueryBuilder('expense')
            ->andWhere('expense.householdId = :householdId')
            ->setParameter('householdId', $householdId)
            ->orderBy('expense.spentOn', 'DESC')
            ->addOrderBy('expense.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function expensesBetween(string $householdId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return $this->entityManager->getRepository(Expense::class)
            ->createQueryBuilder('expense')
            ->andWhere('expense.householdId = :householdId')
            ->andWhere('expense.spentOn >= :from')
            ->andWhere('expense.spentOn <= :to')
            ->setParameter('householdId', $householdId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('expense.spentOn', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function recurringBillsForHousehold(string $householdId): array
    {
        return $this->entityManager->getRepository(RecurringBill::class)->findBy(
            ['householdId' => $householdId, 'active' => true],
            ['dueDay' => 'ASC', 'name' => 'ASC'],
        );
    }
}
