<?php

namespace App\Home\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class AddHomeMaintenanceTaskCommand implements Command
{
    public function __construct(
        public string $householdId,
        public string $title,
        public string $area,
        public string $nextDueAt,
        public string $recurrenceType,
        public ?string $assignedMemberId,
        public string $priority,
        public ?string $notes,
    ) {
    }
}
