<?php

namespace App\Home\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'home_maintenance_tasks')]
#[ORM\Index(name: 'IDX_HOME_MAINTENANCE_HOUSEHOLD_STATUS_DUE', columns: ['household_id', 'status', 'next_due_at'])]
#[ORM\Index(name: 'IDX_HOME_MAINTENANCE_HOUSEHOLD_DELETED', columns: ['household_id', 'deleted_at'])]
class HomeMaintenanceTask
{
    public const RECURRENCE_NONE = 'none';
    public const RECURRENCE_DAILY = 'daily';
    public const RECURRENCE_WEEKLY = 'weekly';
    public const RECURRENCE_MONTHLY = 'monthly';
    public const RECURRENCE_YEARLY = 'yearly';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $householdId;

    #[ORM\Column(type: 'string', length: 160)]
    private string $title;

    #[ORM\Column(type: 'string', length: 80)]
    private string $area;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $nextDueAt;

    #[ORM\Column(type: 'string', length: 16)]
    private string $recurrenceType;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $assignedMemberId;

    #[ORM\Column(type: 'string', length: 16)]
    private string $priority;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes;

    #[ORM\Column(type: 'string', length: 16)]
    private string $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    public function __construct(
        string $id,
        string $householdId,
        string $title,
        string $area,
        DateTimeImmutable $nextDueAt,
        string $recurrenceType,
        ?string $assignedMemberId,
        string $priority,
        ?string $notes,
    ) {
        $this->id = $id;
        $this->householdId = $householdId;
        $this->createdAt = new DateTimeImmutable();
        $this->status = self::STATUS_ACTIVE;
        $this->changeDetails($title, $area, $nextDueAt, $recurrenceType, $assignedMemberId, $priority, $notes);
    }

    public function changeDetails(
        string $title,
        string $area,
        DateTimeImmutable $nextDueAt,
        string $recurrenceType,
        ?string $assignedMemberId,
        string $priority,
        ?string $notes,
    ): void {
        $title = trim($title);
        $area = trim($area);
        $notes = $notes !== null && trim($notes) !== '' ? trim($notes) : null;

        if ($title === '') {
            throw new InvalidArgumentException('Task title is required.');
        }

        if ($area === '') {
            throw new InvalidArgumentException('Task area is required.');
        }

        if (!in_array($recurrenceType, self::recurrenceTypes(), true)) {
            throw new InvalidArgumentException('Unsupported recurrence type.');
        }

        if (!in_array($priority, self::priorities(), true)) {
            throw new InvalidArgumentException('Unsupported priority.');
        }

        $this->title = $title;
        $this->area = $area;
        $this->nextDueAt = $nextDueAt;
        $this->recurrenceType = $recurrenceType;
        $this->assignedMemberId = $assignedMemberId !== null && trim($assignedMemberId) !== '' ? trim($assignedMemberId) : null;
        $this->priority = $priority;
        $this->notes = $notes;
    }

    public function complete(DateTimeImmutable $completedAt): void
    {
        $this->completedAt = $completedAt;

        if ($this->recurrenceType === self::RECURRENCE_NONE) {
            $this->status = self::STATUS_COMPLETED;

            return;
        }

        $this->status = self::STATUS_ACTIVE;
        $this->nextDueAt = $this->nextDueAt->modify($this->recurrenceInterval());
    }

    public function delete(): void
    {
        $this->deletedAt ??= new DateTimeImmutable();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function householdId(): string
    {
        return $this->householdId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function area(): string
    {
        return $this->area;
    }

    public function nextDueAt(): DateTimeImmutable
    {
        return $this->nextDueAt;
    }

    public function recurrenceType(): string
    {
        return $this->recurrenceType;
    }

    public function assignedMemberId(): ?string
    {
        return $this->assignedMemberId;
    }

    public function priority(): string
    {
        return $this->priority;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    /**
     * @return list<string>
     */
    public static function recurrenceTypes(): array
    {
        return [
            self::RECURRENCE_NONE,
            self::RECURRENCE_DAILY,
            self::RECURRENCE_WEEKLY,
            self::RECURRENCE_MONTHLY,
            self::RECURRENCE_YEARLY,
        ];
    }

    /**
     * @return list<string>
     */
    public static function priorities(): array
    {
        return [
            self::PRIORITY_LOW,
            self::PRIORITY_NORMAL,
            self::PRIORITY_HIGH,
        ];
    }

    private function recurrenceInterval(): string
    {
        return match ($this->recurrenceType) {
            self::RECURRENCE_DAILY => '+1 day',
            self::RECURRENCE_WEEKLY => '+1 week',
            self::RECURRENCE_MONTHLY => '+1 month',
            self::RECURRENCE_YEARLY => '+1 year',
            default => '+0 days',
        };
    }
}
