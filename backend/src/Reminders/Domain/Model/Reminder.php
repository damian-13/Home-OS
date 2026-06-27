<?php

namespace App\Reminders\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'reminders')]
#[ORM\Index(name: 'IDX_REMINDERS_HOUSEHOLD_STATUS_DUE', columns: ['household_id', 'status', 'due_at'])]
#[ORM\Index(name: 'IDX_REMINDERS_HOUSEHOLD_DELETED', columns: ['household_id', 'deleted_at'])]
class Reminder
{
    public const RECURRENCE_NONE = 'none';
    public const RECURRENCE_DAILY = 'daily';
    public const RECURRENCE_WEEKLY = 'weekly';
    public const RECURRENCE_MONTHLY = 'monthly';
    public const RECURRENCE_YEARLY = 'yearly';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED = 'skipped';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $householdId;

    #[ORM\Column(type: 'string', length: 160)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $dueAt;

    #[ORM\Column(type: 'string', length: 16)]
    private string $recurrenceType;

    #[ORM\Column(type: 'string', length: 40, nullable: true)]
    private ?string $relatedType;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $relatedId;

    #[ORM\Column(type: 'string', length: 16)]
    private string $status;

    #[ORM\Column(type: 'string', length: 16)]
    private string $priority;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $skippedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    public function __construct(
        string $id,
        string $householdId,
        string $title,
        ?string $note,
        DateTimeImmutable $dueAt,
        string $recurrenceType,
        ?string $relatedType,
        ?string $relatedId,
        string $priority,
    ) {
        $this->id = $id;
        $this->householdId = $householdId;
        $this->status = self::STATUS_PENDING;
        $this->createdAt = new DateTimeImmutable();
        $this->changeDetails($title, $note, $dueAt, $recurrenceType, $relatedType, $relatedId, $priority);
    }

    public function changeDetails(
        string $title,
        ?string $note,
        DateTimeImmutable $dueAt,
        string $recurrenceType,
        ?string $relatedType,
        ?string $relatedId,
        string $priority,
    ): void {
        $title = trim($title);
        $note = $note !== null && trim($note) !== '' ? trim($note) : null;
        $relatedType = $relatedType !== null && trim($relatedType) !== '' ? trim($relatedType) : null;
        $relatedId = $relatedId !== null && trim($relatedId) !== '' ? trim($relatedId) : null;

        if ($title === '') {
            throw new InvalidArgumentException('Reminder title is required.');
        }

        if (!in_array($recurrenceType, self::recurrenceTypes(), true)) {
            throw new InvalidArgumentException('Unsupported reminder recurrence type.');
        }

        if (!in_array($priority, self::priorities(), true)) {
            throw new InvalidArgumentException('Unsupported reminder priority.');
        }

        $this->title = $title;
        $this->note = $note;
        $this->dueAt = $dueAt;
        $this->recurrenceType = $recurrenceType;
        $this->relatedType = $relatedType;
        $this->relatedId = $relatedId;
        $this->priority = $priority;
    }

    public function complete(DateTimeImmutable $completedAt): void
    {
        $this->completedAt = $completedAt;

        if ($this->recurrenceType === self::RECURRENCE_NONE) {
            $this->status = self::STATUS_COMPLETED;

            return;
        }

        $this->status = self::STATUS_PENDING;
        $this->dueAt = $this->dueAt->modify($this->recurrenceInterval());
    }

    public function skip(DateTimeImmutable $skippedAt): void
    {
        $this->skippedAt = $skippedAt;

        if ($this->recurrenceType === self::RECURRENCE_NONE) {
            $this->status = self::STATUS_SKIPPED;

            return;
        }

        $this->status = self::STATUS_PENDING;
        $this->dueAt = $this->dueAt->modify($this->recurrenceInterval());
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

    public function note(): ?string
    {
        return $this->note;
    }

    public function dueAt(): DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function recurrenceType(): string
    {
        return $this->recurrenceType;
    }

    public function relatedType(): ?string
    {
        return $this->relatedType;
    }

    public function relatedId(): ?string
    {
        return $this->relatedId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function priority(): string
    {
        return $this->priority;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function skippedAt(): ?DateTimeImmutable
    {
        return $this->skippedAt;
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
