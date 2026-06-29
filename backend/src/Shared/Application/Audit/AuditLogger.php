<?php

namespace App\Shared\Application\Audit;

use App\Identity\Domain\Model\UserAccount;
use App\Shared\Domain\Model\AuditLog;
use App\Shared\Domain\Uuid;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AuditLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function record(
        string $householdId,
        UserAccount $actor,
        string $entityType,
        string $entityId,
        string $action,
        string $summary,
        array $metadata = [],
    ): void {
        $this->entityManager->persist(new AuditLog(
            (string) Uuid::new(),
            $householdId,
            $actor->id(),
            $entityType,
            $entityId,
            $action,
            $summary,
            $metadata,
        ));
        $this->entityManager->flush();
    }
}
