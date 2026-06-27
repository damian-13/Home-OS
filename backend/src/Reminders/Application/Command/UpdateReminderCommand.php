<?php

namespace App\Reminders\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class UpdateReminderCommand implements Command
{
    public function __construct(
        public string $householdId,
        public string $reminderId,
        public string $title,
        public ?string $note,
        public string $dueAt,
        public string $recurrenceType,
        public ?string $relatedType,
        public ?string $relatedId,
        public string $priority,
    ) {
    }
}
