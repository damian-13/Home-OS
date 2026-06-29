<?php

namespace App\Documents\Infrastructure\Persistence;

use App\Documents\Domain\Model\Document;
use App\Documents\Domain\Repository\DocumentRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class DoctrineDocumentRepository implements DocumentRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Document $document): void
    {
        $this->entityManager->persist($document);
        $this->entityManager->flush();
    }

    public function get(string $householdId, string $documentId): Document
    {
        $document = $this->entityManager->getRepository(Document::class)->findOneBy([
            'id' => $documentId,
            'householdId' => $householdId,
            'deletedAt' => null,
        ]);

        if (!$document instanceof Document) {
            throw new NotFoundHttpException(sprintf('Document "%s" was not found.', $documentId));
        }

        return $document;
    }

    public function documentsForHousehold(string $householdId): array
    {
        return $this->entityManager->getRepository(Document::class)
            ->createQueryBuilder('document')
            ->andWhere('document.householdId = :householdId')
            ->andWhere('document.deletedAt IS NULL')
            ->setParameter('householdId', $householdId)
            ->orderBy('document.expiresAt', 'ASC')
            ->addOrderBy('document.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function expiredDocuments(string $householdId, DateTimeImmutable $today, int $limit = 10): array
    {
        return $this->entityManager->getRepository(Document::class)
            ->createQueryBuilder('document')
            ->andWhere('document.householdId = :householdId')
            ->andWhere('document.deletedAt IS NULL')
            ->andWhere('document.expiresAt IS NOT NULL')
            ->andWhere('document.expiresAt < :today')
            ->setParameter('householdId', $householdId)
            ->setParameter('today', $today)
            ->orderBy('document.expiresAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function expiringDocuments(string $householdId, DateTimeImmutable $today, int $days = 30, int $limit = 10): array
    {
        return $this->entityManager->getRepository(Document::class)
            ->createQueryBuilder('document')
            ->andWhere('document.householdId = :householdId')
            ->andWhere('document.deletedAt IS NULL')
            ->andWhere('document.expiresAt IS NOT NULL')
            ->andWhere('document.expiresAt >= :today')
            ->andWhere('document.expiresAt <= :until')
            ->setParameter('householdId', $householdId)
            ->setParameter('today', $today)
            ->setParameter('until', $today->modify(sprintf('+%d days', $days)))
            ->orderBy('document.expiresAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countDocuments(string $householdId): int
    {
        return (int) $this->entityManager->getRepository(Document::class)
            ->createQueryBuilder('document')
            ->select('COUNT(document.id)')
            ->andWhere('document.householdId = :householdId')
            ->andWhere('document.deletedAt IS NULL')
            ->setParameter('householdId', $householdId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
