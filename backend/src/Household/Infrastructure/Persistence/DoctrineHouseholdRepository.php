<?php

namespace App\Household\Infrastructure\Persistence;

use App\Household\Domain\Model\Household;
use App\Household\Domain\Repository\HouseholdRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class DoctrineHouseholdRepository implements HouseholdRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Household $household): void
    {
        $this->entityManager->persist($household);
        $this->entityManager->flush();
    }

    public function get(string $householdId): Household
    {
        $household = $this->entityManager->find(Household::class, $householdId);

        if (!$household instanceof Household) {
            throw new NotFoundHttpException(sprintf('Household "%s" was not found.', $householdId));
        }

        return $household;
    }
}
