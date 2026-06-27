<?php

namespace App\Home\Infrastructure\Persistence;

use App\Home\Domain\Model\HomeMaintenanceTask;
use App\Home\Domain\Repository\HomeMaintenanceRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class DoctrineHomeMaintenanceRepository implements HomeMaintenanceRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(HomeMaintenanceTask $task): void
    {
        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    public function get(string $householdId, string $taskId): HomeMaintenanceTask
    {
        $task = $this->entityManager->getRepository(HomeMaintenanceTask::class)->findOneBy([
            'id' => $taskId,
            'householdId' => $householdId,
            'deletedAt' => null,
        ]);

        if (!$task instanceof HomeMaintenanceTask) {
            throw new NotFoundHttpException(sprintf('Home maintenance task "%s" was not found.', $taskId));
        }

        return $task;
    }

    public function tasksForHousehold(string $householdId): array
    {
        return $this->entityManager->getRepository(HomeMaintenanceTask::class)
            ->createQueryBuilder('task')
            ->andWhere('task.householdId = :householdId')
            ->andWhere('task.deletedAt IS NULL')
            ->setParameter('householdId', $householdId)
            ->orderBy('task.status', 'ASC')
            ->addOrderBy('task.nextDueAt', 'ASC')
            ->addOrderBy('task.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function overdueTasks(string $householdId, DateTimeImmutable $today, int $limit = 10): array
    {
        return $this->entityManager->getRepository(HomeMaintenanceTask::class)
            ->createQueryBuilder('task')
            ->andWhere('task.householdId = :householdId')
            ->andWhere('task.deletedAt IS NULL')
            ->andWhere('task.status = :status')
            ->andWhere('task.nextDueAt < :today')
            ->setParameter('householdId', $householdId)
            ->setParameter('status', HomeMaintenanceTask::STATUS_ACTIVE)
            ->setParameter('today', $today)
            ->orderBy('task.nextDueAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function upcomingTasks(string $householdId, DateTimeImmutable $today, int $days = 14, int $limit = 10): array
    {
        return $this->entityManager->getRepository(HomeMaintenanceTask::class)
            ->createQueryBuilder('task')
            ->andWhere('task.householdId = :householdId')
            ->andWhere('task.deletedAt IS NULL')
            ->andWhere('task.status = :status')
            ->andWhere('task.nextDueAt >= :today')
            ->andWhere('task.nextDueAt <= :until')
            ->setParameter('householdId', $householdId)
            ->setParameter('status', HomeMaintenanceTask::STATUS_ACTIVE)
            ->setParameter('today', $today)
            ->setParameter('until', $today->modify(sprintf('+%d days', $days)))
            ->orderBy('task.nextDueAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
