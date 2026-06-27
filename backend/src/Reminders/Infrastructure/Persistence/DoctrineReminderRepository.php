<?php

namespace App\Reminders\Infrastructure\Persistence;

use App\Reminders\Domain\Model\Reminder;
use App\Reminders\Domain\Repository\ReminderRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class DoctrineReminderRepository implements ReminderRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Reminder $reminder): void
    {
        $this->entityManager->persist($reminder);
        $this->entityManager->flush();
    }

    public function get(string $householdId, string $reminderId): Reminder
    {
        $reminder = $this->entityManager->getRepository(Reminder::class)->findOneBy([
            'id' => $reminderId,
            'householdId' => $householdId,
            'deletedAt' => null,
        ]);

        if (!$reminder instanceof Reminder) {
            throw new NotFoundHttpException(sprintf('Reminder "%s" was not found.', $reminderId));
        }

        return $reminder;
    }

    public function remindersForHousehold(string $householdId): array
    {
        return $this->entityManager->getRepository(Reminder::class)
            ->createQueryBuilder('reminder')
            ->andWhere('reminder.householdId = :householdId')
            ->andWhere('reminder.deletedAt IS NULL')
            ->setParameter('householdId', $householdId)
            ->orderBy('reminder.status', 'DESC')
            ->addOrderBy('reminder.dueAt', 'ASC')
            ->addOrderBy('reminder.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function overdueReminders(string $householdId, DateTimeImmutable $today, int $limit = 10): array
    {
        return $this->pendingDueQuery($householdId)
            ->andWhere('reminder.dueAt < :today')
            ->setParameter('today', $today)
            ->orderBy('reminder.dueAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function dueTodayReminders(string $householdId, DateTimeImmutable $today, int $limit = 10): array
    {
        return $this->pendingDueQuery($householdId)
            ->andWhere('reminder.dueAt = :today')
            ->setParameter('today', $today)
            ->orderBy('reminder.priority', 'DESC')
            ->addOrderBy('reminder.dueAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function upcomingReminders(string $householdId, DateTimeImmutable $today, int $days = 14, int $limit = 10): array
    {
        return $this->pendingDueQuery($householdId)
            ->andWhere('reminder.dueAt > :today')
            ->andWhere('reminder.dueAt <= :until')
            ->setParameter('today', $today)
            ->setParameter('until', $today->modify(sprintf('+%d days', $days)))
            ->orderBy('reminder.dueAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    private function pendingDueQuery(string $householdId)
    {
        return $this->entityManager->getRepository(Reminder::class)
            ->createQueryBuilder('reminder')
            ->andWhere('reminder.householdId = :householdId')
            ->andWhere('reminder.deletedAt IS NULL')
            ->andWhere('reminder.status = :status')
            ->setParameter('householdId', $householdId)
            ->setParameter('status', Reminder::STATUS_PENDING);
    }
}
