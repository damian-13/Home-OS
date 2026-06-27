<?php

namespace App\Reminders\Application\Dto;

use App\Reminders\Domain\Model\Reminder;

final readonly class ReminderView
{
    public function __construct(
        public string $id,
        public string $householdId,
        public string $title,
        public ?string $note,
        public string $dueAt,
        public string $recurrenceType,
        public ?string $relatedType,
        public ?string $relatedId,
        public string $status,
        public string $priority,
        public string $createdAt,
        public ?string $completedAt,
        public ?string $skippedAt,
    ) {
    }

    public static function fromReminder(Reminder $reminder): self
    {
        return new self(
            $reminder->id(),
            $reminder->householdId(),
            $reminder->title(),
            $reminder->note(),
            $reminder->dueAt()->format('Y-m-d'),
            $reminder->recurrenceType(),
            $reminder->relatedType(),
            $reminder->relatedId(),
            $reminder->status(),
            $reminder->priority(),
            $reminder->createdAt()->format(DATE_ATOM),
            $reminder->completedAt()?->format(DATE_ATOM),
            $reminder->skippedAt()?->format(DATE_ATOM),
        );
    }
}
