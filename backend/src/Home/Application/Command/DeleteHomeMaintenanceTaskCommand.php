<?php

namespace App\Home\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class DeleteHomeMaintenanceTaskCommand implements Command
{
    public function __construct(
        public string $householdId,
        public string $taskId,
    ) {
    }
}
