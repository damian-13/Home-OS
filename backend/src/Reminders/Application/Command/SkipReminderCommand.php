<?php

namespace App\Reminders\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class SkipReminderCommand implements Command
{
    public function __construct(
        public string $householdId,
        public string $reminderId,
    ) {
    }
}
