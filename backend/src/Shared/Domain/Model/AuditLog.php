<?php

namespace App\Shared\Domain\Model;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_logs')]
#[ORM\Index(name: 'IDX_AUDIT_LOGS_HOUSEHOLD_CHANGED', columns: ['household_id', 'changed_at'])]
#[ORM\Index(name: 'IDX_AUDIT_LOGS_ENTITY', columns: ['entity_type', 'entity_id'])]
final class AuditLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'household_id', type: 'string', length: 36)]
    private string $householdId;

    #[ORM\Column(name: 'actor_user_id', type: 'string', length: 36)]
    private string $actorUserId;

    #[ORM\Column(name: 'entity_type', type: 'string', length: 80)]
    private string $entityType;

    #[ORM\Column(name: 'entity_id', type: 'string', length: 80)]
    private string $entityId;

    #[ORM\Column(type: 'string', length: 24)]
    private string $action;

    #[ORM\Column(name: 'changed_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $changedAt;

    #[ORM\Column(type: 'string', length: 255)]
    private string $summary;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $metadata;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $id,
        string $householdId,
        string $actorUserId,
        string $entityType,
        string $entityId,
        string $action,
        string $summary,
        array $metadata = [],
    ) {
        $this->id = $id;
        $this->householdId = $householdId;
        $this->actorUserId = $actorUserId;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->action = $action;
        $this->summary = mb_substr($summary, 0, 255);
        $this->metadata = $metadata;
        $this->changedAt = new DateTimeImmutable();
    }
}
