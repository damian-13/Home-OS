<?php

namespace App\Health\Infrastructure\Persistence;

use App\Health\Domain\Model\BloodTest;
use App\Health\Domain\Model\BloodTestMarker;
use App\Health\Domain\Repository\HealthRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineHealthRepository implements HealthRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function saveBloodTest(BloodTest $bloodTest): void
    {
        $this->entityManager->persist($bloodTest);
        $this->entityManager->flush();
    }

    public function latestBloodTests(string $householdId, ?string $memberId = null, int $limit = 10): array
    {
        $builder = $this->entityManager->getRepository(BloodTest::class)
            ->createQueryBuilder('bloodTest')
            ->andWhere('bloodTest.householdId = :householdId')
            ->setParameter('householdId', $householdId)
            ->orderBy('bloodTest.testedAt', 'DESC')
            ->addOrderBy('bloodTest.id', 'DESC')
            ->setMaxResults($limit);

        if ($memberId) {
            $builder
                ->andWhere('bloodTest.memberId = :memberId')
                ->setParameter('memberId', $memberId);
        }

        return $builder->getQuery()->getResult();
    }

    public function latestOutOfRangeMarkers(string $householdId, ?string $memberId = null, int $limit = 10): array
    {
        $builder = $this->entityManager->getRepository(BloodTestMarker::class)
            ->createQueryBuilder('marker')
            ->join('marker.bloodTest', 'bloodTest')
            ->andWhere('bloodTest.householdId = :householdId')
            ->andWhere('marker.status IN (:statuses)')
            ->setParameter('householdId', $householdId)
            ->setParameter('statuses', ['low', 'high'])
            ->orderBy('bloodTest.testedAt', 'DESC')
            ->setMaxResults($limit);

        if ($memberId) {
            $builder
                ->andWhere('bloodTest.memberId = :memberId')
                ->setParameter('memberId', $memberId);
        }

        return $builder->getQuery()->getResult();
    }

    public function markerHistory(string $householdId, string $markerName, ?string $memberId = null, int $limit = 20): array
    {
        $builder = $this->entityManager->getRepository(BloodTestMarker::class)
            ->createQueryBuilder('marker')
            ->join('marker.bloodTest', 'bloodTest')
            ->andWhere('bloodTest.householdId = :householdId')
            ->andWhere('LOWER(marker.name) = LOWER(:markerName)')
            ->setParameter('householdId', $householdId)
            ->setParameter('markerName', $markerName)
            ->orderBy('bloodTest.testedAt', 'DESC')
            ->setMaxResults($limit);

        if ($memberId) {
            $builder
                ->andWhere('bloodTest.memberId = :memberId')
                ->setParameter('memberId', $memberId);
        }

        return $builder->getQuery()->getResult();
    }

    public function markerNames(string $householdId, ?string $memberId = null): array
    {
        $builder = $this->entityManager->getRepository(BloodTestMarker::class)
            ->createQueryBuilder('marker')
            ->select('DISTINCT marker.name')
            ->join('marker.bloodTest', 'bloodTest')
            ->andWhere('bloodTest.householdId = :householdId')
            ->setParameter('householdId', $householdId)
            ->orderBy('marker.name', 'ASC');

        if ($memberId) {
            $builder
                ->andWhere('bloodTest.memberId = :memberId')
                ->setParameter('memberId', $memberId);
        }

        return array_map(
            static fn (array $row): string => $row['name'],
            $builder->getQuery()->getArrayResult(),
        );
    }
}
